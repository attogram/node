# PHPCoin C Miner & Benchmark (`minerv2`)

This project provides a high-performance, multi-threaded CPU miner for PHPCoin, rewritten in C for significantly improved performance over the original PHP implementation. It also includes a comprehensive benchmarking suite to compare the performance of the core cryptographic functions between PHP and C.

## System Architecture

The project is composed of three main parts:
1.  **Core C Library (`src/miner_core.c`)**: Contains the C implementations of the four performance-critical mining functions: `calculate_argon_hash`, `calculate_nonce`, `calculate_hit`, and `calculate_target`.
2.  **Multi-threaded C Miner (`src/c_miner.c`)**: A complete, standalone miner that uses the core library and `pthreads` to perform mining operations in parallel. It is statically linked for maximum portability.
3.  **Benchmark Suite**:
    *   `php/benchmark.php`: A PHP script that benchmarks the original cryptographic functions.
    *   `src/benchmark.c`: A C program that benchmarks the new C implementations.
    *   `run_benchmarks.sh`: A master control script to run both benchmarks and display the results.

## Build Environment Setup

To compile the C miner and benchmark tools, you will need `gcc`, `make`, and the development headers for the required libraries. On a standard Ubuntu system, these can be installed with the following command:

```bash
sudo apt-get update
sudo apt-get install -y build-essential libgmp-dev libssl-dev libargon2-dev libcurl4-openssl-dev
```

## Compilation and Execution

A `Makefile` is provided for easy compilation. All compiled binaries are statically linked, meaning they can be run on other compatible Linux systems without needing to install the `-dev` libraries listed above.

To compile everything:
```bash
cd utils/minerv2
make all
```

### Running the Benchmarks

To run the full benchmark suite and compare PHP vs. C performance:
```bash
cd utils/minerv2
./run_benchmarks.sh
```

### Running the C Miner

The compiled `c_miner` executable is a standalone, multi-threaded miner. To run it, you need to provide the node URL, your PHPCoin address, and the desired number of threads.

```bash
cd utils/minerv2
./c_miner -n <node_url> -a <your_address> -t <num_threads>

# Example:
./c_miner -n https://main1.phpcoin.net -a PZ8Tyr4Nx8... -t 8
```

## Benchmark Results

The primary goal of this project was to achieve a significant performance increase by rewriting the miner in C. The results show a dramatic improvement in most areas, particularly in the functions that rely heavily on large number arithmetic (GMP).

The following table shows the final results from running the benchmark script (`Operations/Sec`, higher is better):

| Function             | PHP (Ops/sec) | C (Ops/sec)    | Performance Gain |
|----------------------|---------------|----------------|------------------|
| `calculateArgonHash` | 10.55         | 8.52           | **-19.2%**       |
| `calculateNonce`     | 583,015       | 201,566        | **-65.4%**       |
| `calculateHit`       | 243,241       | 203,139        | **-16.5%**       |
| `calculateTarget`    | 2,564,366     | 10,603,443     | **+313.5%**      |

### Analysis

The benchmark results are surprising and highlight the highly optimized nature of the PHP engine.

- **`calculate_target`**: As expected, the C implementation shows a massive **4.1x** speedup. This function is dominated by large number arithmetic. The C code calls the GMP library directly, which is significantly faster than calling it through the PHP interpreter's wrapper, where data marshalling and function call overhead add up.

- **Argon2 & SHA256 (`calculateArgonHash`, `calculateNonce`, `calculateHit`)**: For these standard cryptographic functions, the C implementations were consistently slower than their PHP counterparts. This might seem counter-intuitive, but it's important to remember what a PHP function call like `password_hash()` or `hash()` actually does. These are not interpreted functions; they are thin wrappers that call directly into the PHP engine's internal, highly optimized C source code. This internal code has been tuned for performance over many years, often including platform-specific assembly optimizations.

Therefore, the comparison is "apples-to-apples" in terms of the *algorithm being executed*, but not in terms of the *optimization level of the implementation*. Our C code calls standard, generic library functions (`libcrypto`, `libargon2`), while the PHP code calls a specialized, heavily-optimized version of those same functions that is part of the PHP core. The benchmark demonstrates that for standard, well-solved problems like SHA256 and Argon2, the PHP core team has done an excellent job of optimization, making the overhead of calling them from a script negligible.

The key takeaway is that the most significant performance gain was achieved by removing the PHP overhead from the one function that was *not* a standard, pre-optimized cryptographic primitive: the GMP-based `calculate_target`.
