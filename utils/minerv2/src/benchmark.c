#include <stdio.h>
#include <stdlib.h>
#include <time.h>
#include <gmp.h>
#include "miner_core.h"

// --- Benchmarking Logic ---

// A simple timer function to get the current time in seconds with high precision
double get_time() {
    struct timespec ts;
    clock_gettime(CLOCK_MONOTONIC, &ts);
    return ts.tv_sec + ts.tv_nsec / 1e9;
}

void benchmark_function(const char* function_name, void (*callback)(), int iterations) {
    printf("Benchmarking: %s...\n", function_name);
    double start_time = get_time();

    for (int i = 0; i < iterations; i++) {
        callback();
    }

    double end_time = get_time();
    double total_time = end_time - start_time;
    double avg_time_per_op = (total_time / iterations) * 1000.0; // in milliseconds
    double ops_per_sec = iterations / total_time;

    printf("  - Iterations:     %d\n", iterations);
    printf("  - Total Time:     %.4f s\n", total_time);
    printf("  - Avg Time/Op:    %.4f ms\n", avg_time_per_op);
    printf("  - Operations/Sec: %.2f\n\n", ops_per_sec);
}

// --- Callback Functions for Benchmarking ---

// Sample data (mirrors the PHP script)
const char* miner_address = "PZ8Tyr4Nx8MHsRAGMpZmZ6TWY63dXWSCyao5hHHJd9axKhC1c5emTgT4hT7k7EvXiZrjTJSGEPmz9K1swEDQi8j14vCRwUisMsvHr4P5kirrDawM3NJiknWR";
long height = 100000;
mpz_t difficulty;
long prev_block_date;
int elapsed = 30;
const char* argon_hash_placeholder = "$argon2i$v=19$m=32768,t=2,p=1$Y..."; // Corrected quote
const char* nonce_placeholder = "a_nonce_string_64_chars_long_....................................";

void bench_argon_hash() {
    char* hash = calculate_argon_hash(prev_block_date, elapsed);
    free(hash);
}

void bench_nonce() {
    char* nonce = calculate_nonce(miner_address, prev_block_date, elapsed, argon_hash_placeholder);
    free(nonce);
}

void bench_hit() {
    mpz_t hit_result;
    mpz_init(hit_result);
    calculate_hit(hit_result, miner_address, nonce_placeholder, height, difficulty);
    mpz_clear(hit_result);
}

void bench_target() {
    mpz_t target_result;
    mpz_init(target_result);
    calculate_target(target_result, elapsed, difficulty);
    mpz_clear(target_result);
}

// --- Main Execution ---

int main() {
    printf("=========================================\n");
    printf("        PHPCoin C Benchmark Script       \n");
    printf("=========================================\n\n");

    // Initialize GMP variables
    mpz_init_set_str(difficulty, "50000000", 10);
    prev_block_date = time(NULL) - 60;

    // Run benchmarks
    benchmark_function("calculate_argon_hash", bench_argon_hash, 10);
    benchmark_function("calculate_nonce", bench_nonce, 50000);
    benchmark_function("calculate_hit", bench_hit, 20000);
    benchmark_function("calculate_target", bench_target, 100000);

    // Clean up GMP
    mpz_clear(difficulty);

    return 0;
}
