#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <pthread.h>
#include <unistd.h>
#include <curl/curl.h>
#include <gmp.h>
#include <stdatomic.h>
#include <time.h>

#include "miner_core.h"

// --- Global State ---
atomic_bool block_found = ATOMIC_VAR_INIT(false);
atomic_long total_hashes = ATOMIC_VAR_INIT(0);
pthread_mutex_t console_mutex = PTHREAD_MUTEX_INITIALIZER;

// --- Data Structures ---

// To hold the JSON response from the node
struct memory {
    char *response;
    size_t size;
};

// To pass data to each mining thread
typedef struct {
    int thread_id;
    char* address;
    char* node;
    long height;
    long block_date;
    mpz_t difficulty;
} thread_data_t;


// --- Networking (libcurl) ---

// Callback function to write curl response to our memory struct
static size_t write_callback(void *data, size_t size, size_t nmemb, void *userp) {
    size_t realsize = size * nmemb;
    struct memory *mem = (struct memory *)userp;

    char *ptr = realloc(mem->response, mem->size + realsize + 1);
    if (ptr == NULL) return 0; // out of memory

    mem->response = ptr;
    memcpy(&(mem->response[mem->size]), data, realsize);
    mem->size += realsize;
    mem->response[mem->size] = 0;

    return realsize;
}

// Super basic JSON parser to extract values. Not robust, but avoids a library dependency.
char* json_extract(const char* json, const char* key) {
    char* key_ptr = strstr(json, key);
    if (!key_ptr) return NULL;

    char* colon_ptr = strchr(key_ptr, ':');
    if (!colon_ptr) return NULL;

    char* start_ptr = colon_ptr + 1;
    while (*start_ptr == ' ' || *start_ptr == '"') start_ptr++;

    char* end_ptr = start_ptr;
    while (*end_ptr != '"' && *end_ptr != ',' && *end_ptr != '}') end_ptr++;

    int len = end_ptr - start_ptr;
    char* value = malloc(len + 1);
    strncpy(value, start_ptr, len);
    value[len] = '\0';
    return value;
}


int get_mining_info(const char* node, long* height, mpz_t difficulty, long* date) {
    CURL *curl;
    CURLcode res;
    struct memory chunk = {0};
    char url[256];
    snprintf(url, sizeof(url), "%s/mine.php?q=info", node);

    curl = curl_easy_init();
    if (curl) {
        curl_easy_setopt(curl, CURLOPT_URL, url);
        curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, write_callback);
        curl_easy_setopt(curl, CURLOPT_WRITEDATA, (void *)&chunk);
        curl_easy_setopt(curl, CURLOPT_TIMEOUT, 10L); // 10 second timeout

        res = curl_easy_perform(curl);
        if (res != CURLE_OK) {
            fprintf(stderr, "curl_easy_perform() failed: %s\n", curl_easy_strerror(res));
            curl_easy_cleanup(curl);
            free(chunk.response);
            return 0;
        }

        char *height_str = json_extract(chunk.response, "\"height\"");
        char *difficulty_str = json_extract(chunk.response, "\"difficulty\"");
        char *date_str = json_extract(chunk.response, "\"date\"");

        if (!height_str || !difficulty_str || !date_str) {
             fprintf(stderr, "Error: Could not parse mining info from node.\n");
             curl_easy_cleanup(curl);
             free(chunk.response);
             return 0;
        }

        *height = atol(height_str) + 1;
        mpz_set_str(difficulty, difficulty_str, 10);
        *date = atol(date_str);

        free(height_str);
        free(difficulty_str);
        free(date_str);
        free(chunk.response);
        curl_easy_cleanup(curl);
        return 1;
    }
    return 0;
}


// --- Mining Thread ---

void* miner_thread(void* arg) {
    thread_data_t* data = (thread_data_t*)arg;
    mpz_t hit, target;
    mpz_inits(hit, target, NULL);

    long attempts = 0;
    time_t last_update = time(NULL);

    while (!block_found) {
        long current_time = time(NULL);
        int elapsed = current_time - data->block_date;
        if (elapsed < 0) elapsed = 0;

        char* argon = calculate_argon_hash(data->block_date, elapsed);
        if (!argon) continue;

        char* nonce = calculate_nonce(data->address, data->block_date, elapsed, argon);
        if (!nonce) {
            free(argon);
            continue;
        }

        calculate_hit(hit, data->address, nonce, data->height, data->difficulty);
        calculate_target(target, elapsed, data->difficulty);

        if (mpz_cmp(hit, target) > 0) {
            block_found = true;

            pthread_mutex_lock(&console_mutex);
            printf("\n\n!!! BLOCK FOUND BY THREAD %d !!!\n", data->thread_id);
            gmp_printf("Height: %ld\nNonce: %s\nHit: %Zd\nTarget: %Zd\n\n",
                data->height, nonce, hit, target);
            pthread_mutex_unlock(&console_mutex);
        }

        free(argon);
        free(nonce);
        attempts++;

        // Update global hash counter every second
        if (current_time > last_update) {
            atomic_fetch_add(&total_hashes, attempts);
            attempts = 0;
            last_update = current_time;
        }
    }

    atomic_fetch_add(&total_hashes, attempts);
    mpz_clears(hit, target, NULL);
    free(data); // Free the thread-specific data
    return NULL;
}

// --- Main Function ---

void print_usage(const char* prog_name) {
    fprintf(stderr, "Usage: %s -n <node_url> -a <address> [-t <threads>]\n", prog_name);
}

int main(int argc, char** argv) {
    char* node = NULL;
    char* address = NULL;
    int num_threads = 4; // Default to 4 threads
    int opt;

    while ((opt = getopt(argc, argv, "n:a:t:")) != -1) {
        switch (opt) {
            case 'n':
                node = optarg;
                break;
            case 'a':
                address = optarg;
                break;
            case 't':
                num_threads = atoi(optarg);
                break;
            default:
                print_usage(argv[0]);
                exit(EXIT_FAILURE);
        }
    }

    if (!node || !address) {
        print_usage(argv[0]);
        exit(EXIT_FAILURE);
    }

    if (num_threads <= 0) num_threads = 1;

    long height, block_date;
    mpz_t difficulty;
    mpz_init(difficulty);

    printf("Fetching initial mining info from %s...\n", node);
    if (!get_mining_info(node, &height, difficulty, &block_date)) {
        fprintf(stderr, "Failed to get mining info. Exiting.\n");
        mpz_clear(difficulty);
        exit(EXIT_FAILURE);
    }

    gmp_printf("Starting miner for address %s\nHeight: %ld\nDifficulty: %Zd\nThreads: %d\n",
        address, height, difficulty, num_threads);
    printf("---------------------------------------------------\n");


    pthread_t* threads = malloc(sizeof(pthread_t) * num_threads);

    for (int i = 0; i < num_threads; i++) {
        thread_data_t* data = malloc(sizeof(thread_data_t));
        data->thread_id = i + 1;
        data->address = address;
        data->node = node;
        data->height = height;
        data->block_date = block_date;
        mpz_init_set(data->difficulty, difficulty);

        pthread_create(&threads[i], NULL, miner_thread, data);
    }

    time_t start_time = time(NULL);
    while (!block_found) {
        sleep(1);
        long current_hashes = atomic_exchange(&total_hashes, 0);
        time_t now = time(NULL);
        double elapsed_sec = difftime(now, start_time);
        if (elapsed_sec < 1) elapsed_sec = 1;

        double hash_rate = (double)current_hashes;
        pthread_mutex_lock(&console_mutex);
        printf("Hash Rate: %.2f H/s\r", hash_rate);
        fflush(stdout);
        pthread_mutex_unlock(&console_mutex);
    }

    for (int i = 0; i < num_threads; i++) {
        pthread_join(threads[i], NULL);
    }

    printf("\nMiner stopped.\n");

    free(threads);
    mpz_clear(difficulty);
    return 0;
}
