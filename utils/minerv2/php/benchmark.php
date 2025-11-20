<?php

// --- Constants and Helper Functions (from PHPCoin codebase) ---

const HASHING_ALGO = PASSWORD_ARGON2I;
const BLOCK_TIME = 60;
const BLOCK_TARGET_MUL = 1000;
const CHAIN_ID = "00";

// A simplified gmp_hexdec function for the benchmark
function gmp_hexdec($n) {
    return gmp_init($n, 16);
}

// Hashing options vary by block height, we'll use the latest for benchmarking
function hashingOptions($height=null) {
    // Simulating latest options from Block::hashingOptions
    return ['memory_cost' => 32768, "time_cost" => 2, "threads" => 1];
}

// --- Core Mining Functions (adapted from Block.php) ---

function calculateArgonHash($prev_block_date, $elapsed, $miner_address) {
    $base = "{$prev_block_date}-{$elapsed}";
    $options = hashingOptions();
    // In the original code, an older version used a salt from the miner address.
    // The latest version does not, but we include it for a complete test.
    // $options['salt'] = substr($miner_address, 0, 16);
    return @password_hash($base, HASHING_ALGO, $options);
}

function calculateNonce($prev_block_date, $elapsed, $argon, $miner_address) {
    $nonceBase = CHAIN_ID . "{$miner_address}-{$prev_block_date}-{$elapsed}-{$argon}";
    return hash("sha256", $nonceBase);
}

function calculateHit($miner_address, $nonce, $height, $difficulty) {
    $base = "{$miner_address}-{$nonce}-{$height}-{$difficulty}";
    $hash = hash("sha256", $base);
    $hash = hash("sha256", $hash);
    $hashPart = substr($hash, 0, 8);
    $value = gmp_hexdec($hashPart);
    if (gmp_cmp($value, 0) == 0) { // Avoid division by zero
        $value = gmp_init(1);
    }
    return gmp_div(gmp_mul(gmp_hexdec("ffffffff"), BLOCK_TARGET_MUL), $value);
}

function calculateTarget($elapsed, $difficulty) {
    if ($elapsed <= 0) {
        return gmp_init(0);
    }
    return gmp_div(gmp_mul($difficulty, BLOCK_TIME), $elapsed);
}

// --- Benchmarking Logic ---

function benchmark_function($function_name, $callback, $iterations = 100) {
    echo "Benchmarking: $function_name...\n";
    $start_time = microtime(true);

    for ($i = 0; $i < $iterations; $i++) {
        $callback();
    }

    $end_time = microtime(true);
    $total_time = $end_time - $start_time;
    $avg_time_per_op = ($total_time / $iterations) * 1000; // in milliseconds
    $ops_per_sec = $iterations / $total_time;

    echo "  - Iterations:     $iterations\n";
    echo "  - Total Time:     " . number_format($total_time, 4) . " s\n";
    echo "  - Avg Time/Op:    " . number_format($avg_time_per_op, 4) . " ms\n";
    echo "  - Operations/Sec: " . number_format($ops_per_sec, 2) . "\n\n";
}

// --- Main Execution ---

echo "=========================================\n";
echo "      PHPCoin PHP Benchmark Script       \n";
echo "=========================================\n\n";

// Sample Data for Benchmarking
$miner_address = "PZ8Tyr4Nx8MHsRAGMpZmZ6TWY63dXWSCyao5hHHJd9axKhC1c5emTgT4hT7k7EvXiZrjTJSGEPmz9K1swEDQi8j14vCRwUisMsvHr4P5kirrDawM3NJiknWR";
$height = 100000; // Example height
$difficulty = gmp_init("50000000");
$prev_block_date = time() - 60;
$elapsed = 30; // 30 seconds elapsed
$argon = '$argon2i$v=19$m=32768,t=2,p=1$Y...'; // Placeholder
$nonce = 'a_nonce_string_64_chars_long_....................................'; // Placeholder

// Benchmark Argon2 Hashing
benchmark_function('calculateArgonHash', function () use ($prev_block_date, $elapsed, $miner_address) {
    calculateArgonHash($prev_block_date, $elapsed, $miner_address);
}, 10); // Argon2 is slow, so fewer iterations

// Benchmark Nonce Calculation (SHA256)
benchmark_function('calculateNonce', function () use ($prev_block_date, $elapsed, $argon, $miner_address) {
    calculateNonce($prev_block_date, $elapsed, $argon, $miner_address);
}, 50000);

// Benchmark Hit Calculation (double SHA256 + GMP)
benchmark_function('calculateHit', function () use ($miner_address, $nonce, $height, $difficulty) {
    calculateHit($miner_address, $nonce, $height, $difficulty);
}, 20000);

// Benchmark Target Calculation (GMP only)
benchmark_function('calculateTarget', function () use ($elapsed, $difficulty) {
    calculateTarget($elapsed, $difficulty);
}, 100000);

?>
