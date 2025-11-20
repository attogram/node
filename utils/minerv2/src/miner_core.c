#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <gmp.h>
#include <openssl/sha.h>
#include <openssl/rand.h> // For generating the salt
#include <argon2.h>
#include "miner_core.h"

// Constants
#define SALT_LEN 16

// Helper function to convert a raw SHA256 hash to a hex string
static void sha256_to_hex(const unsigned char* hash, char* hex_string) {
    for(int i = 0; i < SHA256_DIGEST_LENGTH; i++) {
        sprintf(hex_string + (i * 2), "%02x", hash[i]);
    }
    hex_string[64] = 0;
}

char* calculate_argon_hash(long prev_block_date, int elapsed) {
    char base[256];
    snprintf(base, sizeof(base), "%ld-%d", prev_block_date, elapsed);

    // Generate a cryptographically secure random salt, just like PHP's password_hash does
    unsigned char salt[SALT_LEN];
    if (RAND_bytes(salt, sizeof(salt)) != 1) {
        fprintf(stderr, "Error: Failed to generate random salt.\n");
        return NULL;
    }

    uint32_t hash_len = 32;
    size_t encoded_len = argon2_encodedlen(
        ARGON2_T_COST,
        ARGON2_M_COST,
        ARGON2_PARALLELISM,
        sizeof(salt),
        hash_len,
        Argon2_i
    );

    char *encoded_hash = (char*)malloc(encoded_len);
    if (!encoded_hash) {
        perror("Failed to allocate memory for Argon2 hash");
        return NULL;
    }

    int result = argon2i_hash_encoded(
        ARGON2_T_COST,
        ARGON2_M_COST,
        ARGON2_PARALLELISM,
        base, strlen(base),
        salt, sizeof(salt),
        hash_len,
        encoded_hash,
        encoded_len
    );

    if (result != ARGON2_OK) {
        fprintf(stderr, "Error creating Argon2 hash: %s\n", argon2_error_message(result));
        free(encoded_hash);
        return NULL;
    }

    return encoded_hash;
}

char* calculate_nonce(const char* miner_address, long prev_block_date, int elapsed, const char* argon_hash) {
    char nonce_base[512];
    snprintf(nonce_base, sizeof(nonce_base), "%s%s-%ld-%d-%s",
        CHAIN_ID, miner_address, prev_block_date, elapsed, argon_hash);

    unsigned char hash[SHA256_DIGEST_LENGTH];
    SHA256((unsigned char*)nonce_base, strlen(nonce_base), hash);

    char *hex_hash = (char*)malloc(65);
    if (!hex_hash) {
        perror("Failed to allocate memory for nonce");
        return NULL;
    }
    sha256_to_hex(hash, hex_hash);

    return hex_hash;
}

void calculate_hit(mpz_t result, const char* miner_address, const char* nonce, long height, const mpz_t difficulty) {
    char base[512];
    char *difficulty_str = mpz_get_str(NULL, 10, difficulty);
    snprintf(base, sizeof(base), "%s-%s-%ld-%s",
        miner_address, nonce, height, difficulty_str);
    free(difficulty_str);


    // Double SHA256
    unsigned char hash1[SHA256_DIGEST_LENGTH];
    SHA256((unsigned char*)base, strlen(base), hash1);
    unsigned char hash2[SHA256_DIGEST_LENGTH];
    SHA256(hash1, sizeof(hash1), hash2);

    // Take the first 8 bytes (64 bits) of the final hash
    char hash_part_hex[17];
    for(int i = 0; i < 8; i++) {
        sprintf(hash_part_hex + i * 2, "%02x", hash2[i]);
    }
    hash_part_hex[16] = 0;

    mpz_t value;
    mpz_init_set_str(value, hash_part_hex, 16);

    if (mpz_cmp_ui(value, 0) == 0) {
        mpz_set_ui(value, 1); // Avoid division by zero
    }

    // result = ("ffffffff" * BLOCK_TARGET_MUL) / value
    mpz_t ffffffff;
    mpz_init_set_str(ffffffff, "ffffffff", 16);

    mpz_t numerator;
    mpz_init(numerator);
    mpz_mul_ui(numerator, ffffffff, BLOCK_TARGET_MUL);

    mpz_fdiv_q(result, numerator, value);

    // Clean up
    mpz_clear(value);
    mpz_clear(ffffffff);
    mpz_clear(numerator);
}

void calculate_target(mpz_t result, int elapsed, const mpz_t difficulty) {
    if (elapsed <= 0) {
        mpz_set_ui(result, 0);
        return;
    }

    // result = (difficulty * BLOCK_TIME) / elapsed
    mpz_t numerator;
    mpz_init(numerator);
    mpz_mul_ui(numerator, difficulty, BLOCK_TIME);

    mpz_fdiv_q_ui(result, numerator, elapsed);

    mpz_clear(numerator);
}
