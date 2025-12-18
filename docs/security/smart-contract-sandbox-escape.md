# Smart Contract Sandbox Escape

This document details vulnerabilities in the Smart Contract sandbox.

## 1. SQL Injection Vulnerability

**Severity:** Critical

**Analysis:** The `query` method in `include/class/sc/SmartContractBase.php` is vulnerable to SQL injection. A malicious smart contract can execute arbitrary SQL, including `UPDATE` and `DELETE` statements, allowing for the complete theft of funds and compromise of the blockchain node.

## 2. Advanced Sandbox Bypass Techniques

The `disable_functions` directive in the smart contract sandbox is more comprehensive than for dapps, but it is not foolproof. Several bypass vectors remain:

*   **Indirect Execution via Callback Functions:** The exclusion of `call_user_func()` and `call_user_func_array()` from the `disable_functions` list is a fundamental design flaw that renders the entire function blacklist meaningless.
*   **File Manipulation:** Standard file I/O functions such as `file_put_contents()` are not disabled. This could be used for a Denial-of-Service attack by filling the `/tmp` partition.
*   **Stream Wrapper Abuse:** The sandbox does not restrict the use of PHP's stream wrappers, such as `phar://` and `php://`, which can be used to bypass `open_basedir`.
*   **Information Disclosure:** Functions like `get_defined_constants()` and `constant()` can be used to leak sensitive information about the host environment, including credentials.
*   **Reflection-Based Bypasses:** The `ReflectionFunction` class can be used to invoke functions indirectly, bypassing the `disable_functions` directive.
*   **Arbitrary Code Execution via `preg_replace()`:** The `preg_replace()` function with the `/e` (evaluate) modifier can be used to execute arbitrary PHP code.
*   **Deserialization Vulnerabilities:** The `unserialize()` function is not disabled, which can lead to object injection and arbitrary code execution.
*   **Other Execution Vectors:** Other functions that can lead to code or command execution are not included in the `disable_functions` list, such as `pcntl_exec()`, `dl()`, and `assert()`.
*   **Server-Side Request Forgery (SSRF):** Functions like `file_get_contents()` and `fsockopen()` can be used to make outbound network requests from the node.
