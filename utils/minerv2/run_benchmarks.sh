#!/bin/bash

# Navigate to the script's directory
cd "$(dirname "$0")"

echo "========================================="
echo "      PHPCoin Benchmark Control Script   "
echo "========================================="
echo

# --- Step 1: Run PHP Benchmark ---
echo "--- Running PHP Benchmark ---"
php php/benchmark.php
echo "--- PHP Benchmark Complete ---"
echo
echo

# --- Step 2: Compile and Run C Benchmark ---
echo "--- Compiling C Benchmark (with static linking) ---"
make clean > /dev/null
make benchmark
if [ $? -ne 0 ]; then
    echo "C benchmark compilation failed. Aborting."
    exit 1
fi
echo "--- Compilation Complete ---"
echo
echo "--- Running C Benchmark ---"
./benchmark
echo "--- C Benchmark Complete ---"
echo

echo "========================================="
echo "             Benchmark Summary           "
echo "========================================="
echo "Review the output above to compare the performance of the PHP and C implementations."
echo "The 'Operations/Sec' metric shows how many times each function could be executed per second."
echo "Higher is better."
echo
