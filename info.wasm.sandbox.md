# Proposal: Replacing the Smart Contract Sandbox with a Wasm Sandbox

## Introduction

This document outlines a proposal to replace the existing smart contract sandbox with a more secure, performant, and robust solution based on WebAssembly (Wasm). The current implementation, while functional, presents significant security risks and is difficult to maintain. By migrating to a Wasm-based sandbox, we can leverage the security guarantees of a modern, industry-standard virtualization technology while retaining the ability to execute PHP-based smart contracts.

## Current System Limitations

The current smart contract sandbox is a custom-built solution that relies on a blacklist of PHP functions to prevent malicious code execution. This approach has several fundamental flaws:

*   **Insecure by Default:** Blacklist-based security is inherently insecure. It is a reactive approach that relies on anticipating all possible attack vectors and blocking them. A single mistake in the blacklist can lead to a complete compromise of the sandbox, potentially allowing for Remote Code Execution (RCE) vulnerabilities.
*   **Difficult to Maintain:** The PHP language has a vast and ever-growing set of functions. Maintaining a comprehensive and up-to-date blacklist is a significant and error-prone undertaking.
*   **Performance Overhead:** The current sandbox implementation introduces significant performance overhead, limiting the complexity and throughput of smart contracts.

## Why Wasm?

WebAssembly is a modern, high-performance, and secure compilation target for a variety of languages, including PHP. By migrating to a Wasm-based sandbox, we can gain the following benefits:

*   **Strong Security Guarantees:** Wasm is designed from the ground up with security in mind. It provides a sandboxed execution environment that is completely isolated from the host system. Wasm modules have no access to the filesystem, network, or other system resources by default. All interactions with the host system must be explicitly exposed through a well-defined API.
*   **Improved Performance:** Wasm is designed for near-native performance. By compiling smart contracts to Wasm, we can significantly improve execution speed and reduce gas costs.
*   **Language Neutrality:** While the initial focus of this proposal is on supporting PHP smart contracts, a Wasm-based sandbox would open the door to supporting other languages in the future, such as Rust, C++, and Go. This would allow us to attract a wider range of developers to our platform.
*   **Industry Standard:** Wasm is a widely adopted industry standard, with support from all major browser vendors and a growing ecosystem of tools and libraries. By adopting Wasm, we can leverage the collective expertise of the Wasm community and benefit from ongoing improvements to the Wasm specification and tooling.

## Learnings from Other Blockchains

Several prominent blockchain platforms have successfully implemented Wasm as their smart contract runtime. Their experiences provide valuable lessons and established best practices that can inform our own implementation.

**NEAR Protocol**
*   **Gas Metering:** Implemented by injecting gas-counting instructions into the Wasm bytecode during compilation.
*   **Tooling:** Strong developer tooling (`near-sdk-rs`) has been crucial for adoption.
*   **Key Pitfall:** Had to restrict non-deterministic floating-point operations after they caused issues.

**Polkadot/Substrate**
*   **Runtime Choice:** Uses Wasmtime, chosen for its strong focus on security and performance.
*   **Gas Metering:** Employs a sophisticated system that charges for memory allocation in addition to CPU operations.
*   **Security:** Runs Wasm in a highly restricted environment with no access to non-deterministic syscalls.

**CosmWasm (Cosmos Ecosystem)**
*   **Determinism:** Enforces strict determinism by rejecting any Wasm module containing non-deterministic instructions at upload time.
*   **Security Model:** Uses a capability-based model where contracts must explicitly request permissions.
*   **Best Practice:** Versions their VM, allowing for upgrades while maintaining determinism for existing contracts.

**EOS (EOSIO)**
*   **Performance:** An early adopter of Wasm, demonstrating its high-performance capabilities.
*   **Key Pitfall:** Required sophisticated resource management to handle issues with contracts timing out.

## Security Benefits of Wasm

A Wasm sandbox provides a much stronger security model than a blacklist-based approach. The security benefits of Wasm are not just theoretical; they are designed into the core of the specification.

*   **Memory Safety:** Wasm modules execute in a sandboxed environment with a linear memory space that is completely isolated from the host system. The Wasm runtime ensures that all memory accesses are within the bounds of this linear memory, effectively eliminating the risk of buffer overflows and other memory-related vulnerabilities.
*   **Control-Flow Integrity (CFI):** Wasm's structured control flow and type-safe indirect calls make it impossible for an attacker to hijack the control flow of a program. This prevents a wide range of attacks, such as Return-Oriented Programming (ROP) and Jump-Oriented Programming (JOP).
*   **Capability-Based Security:** Wasm modules have no access to the host system by default. All interactions with the host, such as reading from a file or making a network request, must be explicitly exposed to the Wasm module through an import mechanism. This "deny-by-default" approach ensures that smart contracts can only access the resources they are explicitly granted permission to use.
*   **Formal Verification:** The Wasm instruction set is small, simple, and well-defined, making it much more amenable to formal verification than a complex, high-level language like PHP. This opens the door to formally proving the correctness and security of smart contracts.

## Proposed Architecture

Based on the learnings from other blockchain projects, we propose a robust and secure architecture for our Wasm-based sandbox. This architecture is designed to address the critical requirements of a blockchain environment: determinism, security, and performance.

### Key Technical Considerations

*   **Determinism is Non-Negotiable:** A blockchain's state transitions must be deterministic. Any non-determinism can lead to consensus failures. To ensure this, the sandbox must:
    *   Forbid or carefully handle floating-point operations.
    *   Block all non-deterministic system calls, such as those for system time or random number generation.
    *   Reject Wasm modules at upload time if they contain non-deterministic instructions.

*   **Gas Metering:** We must prevent denial-of-service attacks by metering resource consumption. The two primary strategies are:
    *   **Injection-based (Static):** Inject gas-counting code into the Wasm bytecode before execution. This is the approach taken by NEAR.
    *   **Runtime-based (Dynamic):** The runtime tracks execution and charges per operation. This is the approach taken by Polkadot.
    *   A hybrid approach using middleware to wrap the Wasm runtime for metering (like CosmWasm) is also a strong possibility. Gas should be charged for memory allocation in addition to CPU cycles.

*   **Memory Management:** Smart contracts must be prevented from consuming excessive amounts of memory. This can be achieved by:
    *   Setting hard limits on linear memory allocation (e.g., CosmWasm's 32MB default).
    *   Charging gas for memory growth.

*   **Host API (Import/Export Interface):** The interface between the Wasm smart contract and the host blockchain must be minimal and secure.
    *   Expose a minimal set of host functions (e.g., for storage, cryptography, and logging).
    *   Contracts should export a standard set of entry points (e.g., `instantiate`, `execute`, `query`).
    *   This small, auditable API surface is critical for security.

### Wasm Runtime Choice

The choice of Wasm runtime is a critical component of the sandbox. The most popular choices in the blockchain space are:

*   **Wasmtime:** Developed by the Bytecode Alliance, it is known for its strong focus on security, correctness, and its use in projects like Polkadot. It is often considered the industry standard for secure, production-ready Wasm execution.
*   **Wasmer:** Known for its high performance and flexibility, supporting multiple backends. It is used by NEAR Protocol.
*   **WasmEdge:** A high-performance runtime optimized for edge computing and serverless applications.

**Recommendation:** We recommend starting with **Wasmtime** due to its battle-tested security focus and formal verification efforts, which are paramount in a blockchain context.

### Specific Recommendations for PHP-to-Wasm

Running PHP inside a Wasm sandbox is a unique challenge. Learnings from projects like the WordPress Playground are highly relevant:

*   **Performance:** The PHP interpreter compiled to Wasm will have performance overhead and potentially slower startup times compared to native execution. We should mitigate this by:
    *   Caching compiled Wasm modules.
    *   Investigating ahead-of-time (AOT) compilation instead of just-in-time (JIT) to improve startup speed.
*   **Security:** While the Wasm sandbox prevents the PHP code from escaping, the PHP code itself can still contain vulnerabilities. We must:
    *   Use a minimal PHP build with unnecessary and dangerous extensions removed.
    *   Continue to restrict dangerous functions at the PHP level as a secondary layer of defense.

### Summary of Recommended Architecture

1.  **Runtime:** Use **Wasmtime** as the core execution engine.
2.  **Gas Metering:** Implement gas metering via middleware that wraps the Wasmtime runtime.
3.  **Host API:** Expose a minimal, capability-based set of host functions for blockchain interaction.
4.  **Determinism:** Enforce strict determinism by validating Wasm modules at upload time.
5.  **Resource Limits:** Enforce conservative memory and execution limits.
6.  **Performance:** Cache compiled Wasm modules to improve performance.

## Migration Path

We propose a phased migration to the new Wasm sandbox to minimize disruption and risk.

1.  **Phase 1: Research and Prototyping (Q3 2024):**
    *   Evaluate and select a Wasm runtime.
    *   Develop a proof-of-concept for compiling the PHP interpreter to Wasm.
    *   Design and implement a prototype of the Host API.
2.  **Phase 2: Development and Testing (Q4 2024):**
    *   Implement the full Host API.
    *   Integrate the Wasm runtime into our existing codebase.
    *   Develop a comprehensive test suite for the new sandbox.
3.  **Phase 3: Testnet Deployment (Q1 2025):**
    *   Deploy the Wasm sandbox to the testnet.
    *   Invite the community to test the new sandbox and provide feedback.
    *   Conduct a security audit of the new sandbox.
4.  **Phase 4: Mainnet Deployment (Q2 2025):**
    *   Deploy the Wasm sandbox to the mainnet.
    *   The existing sandbox will be deprecated and eventually removed.

## Roadmap

The initial focus of this project is to replace the existing PHP sandbox with a more secure Wasm-based solution. However, the adoption of Wasm opens the door to a number of exciting future possibilities:

*   **Multi-Language Support:** Once the Wasm sandbox is in place, we can begin to add support for other languages, such as Rust, C++, and Go. This will make our platform more attractive to a wider range of developers.
*   **Off-Chain Computation:** Wasm can be used for off-chain computation, allowing us to move expensive computations off the main chain and onto a layer-2 solution.
*   **Improved Tooling:** We will invest in developing a comprehensive suite of tools for developing, testing, and debugging Wasm-based smart contracts.

## Conclusion

The migration to a Wasm-based sandbox represents a significant step forward for our platform. It will provide a much more secure, performant, and flexible foundation for our smart contract ecosystem. By adopting this industry-standard technology, we can future-proof our platform and position ourselves for long-term success.

## Further Reading

*   **CosmWasm Documentation:** For best practices on Wasm smart contracts.
*   **Wasmtime Security Documentation:** To understand the threat model of the recommended runtime.
*   **NEAR's Gas Parameter Documentation:** For insights into gas cost calculation.
*   **Parity's ink! Design:** For examples of elegant contract interface patterns.
