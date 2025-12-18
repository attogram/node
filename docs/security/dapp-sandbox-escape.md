# Dapp Sandbox Escape

This document details vulnerabilities in the Dapp sandbox.

## 1. Sandbox Escape via `dapps_exec`

**Severity:** Critical

**Description:** The `dapps_exec` function in `include/class/Dapps.php` provides a mechanism for a dapp to execute arbitrary PHP code on the node that is running it. This function is protected by a check, `Dapps::isLocal()`, which is intended to ensure that only "local" dapps can use it. However, if a user can be tricked into installing a malicious dapp, that dapp is considered "local" and can use `dapps_exec` to execute any code it wants, completely bypassing the sandbox.

## 2. Dapp Propagation

**Severity:** Critical

**Analysis:** The "propagate dapps" feature, when enabled, allows a node to automatically distribute its local dapps to all of its peers. This means that if a node operator is tricked into installing a malicious dapp, that dapp will be automatically propagated to all other nodes on the network that have enabled this feature. This could lead to a widespread compromise of the network.

## 3. Advanced Sandbox Bypass Techniques

The `disable_functions` directive in the sandbox is not foolproof. An attacker can use several techniques to bypass these restrictions:

*   **Indirect Execution via Callback Functions:** The exclusion of `call_user_func()` and `call_user_func_array()` from the `disable_functions` list allows any remote dapp to trivially bypass the sandbox and achieve Remote Code Execution (RCE).
*   **Web Shell via File Manipulation:** The `disable_functions` directive does not block standard file I/O functions. An attacker can write a new PHP file (a "web shell") to a location on the.
*   **Other Execution Vectors:** Other functions that can lead to code or command execution are not included in the `disable_functions` list, such as `pcntl_exec()`, `dl()`, and `assert()`.
*   **Server-Side Request Forgery (SSRF):** Functions like `file_get_contents()` and `fsockopen()` can be used to initiate requests from the server, allowing an attacker to probe the node's internal network.

## 4. Insecure Deserialization

*   **What it is:** A critical remote code execution (RCE) vulnerability exists due to a combination of two issues: an insecure deserialization flaw in the `Pajax` class and a sandbox escape in the dapp execution environment.
*   **How to exploit:** The attack requires the node operator to be running a malicious or compromised dapp.
    1.  **Sandbox Escape:** The dapp, running in a restricted sandbox, calls the `dapps_exec()` function. This function allows the dapp to send a string of arbitrary PHP code to the main, unsandboxed node process, which then executes it via `eval()`.
    2.  **Trigger Deserialization:** The code executed via `dapps_exec()` can then instantiate and call the vulnerable `Pajax` class.
    3.  **Achieve RCE:** The attacker crafts a serialized PHP object (a "gadget") that, when unserialized by `Pajax`, will execute a system command. The `Forker` class can be used as a gadget for this purpose.
*   **Attack Vector:** An attacker would trigger the exploit by making a web request to the malicious dapp file running on the compromised node (e.g., `curl http://<node-ip>/dapps.php?url=<dapp-id>/exploit.php`).
*   **Mitigating Factor:** The `dapps_is_local()` check prevents a remote attacker from simply running their own arbitrary dapp on the node. The vulnerability can only be exploited if the dapp the node operator *chooses* to host is malicious or has a separate vulnerability.
*   **How to defend:**
    1.  **Remove Sandbox Escape:** The `dapps_exec()` function is a dangerous backdoor that nullifies the security of the dapp sandbox. It should be removed from `include/dapps.functions.php` and `include/class/Dapps.php`.
    2.  **Delete `Pajax.php`:** The `Pajax.php` class is a latent vulnerability and appears to be unused in the main application. It should be deleted.
