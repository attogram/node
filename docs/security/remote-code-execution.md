# Remote Code Execution

This document details remote code execution vulnerabilities.

## 1. Command Injection

*   **What it is:** The "Add Peer" functionality in the admin panel (`web/apps/admin/index.php`) passes an unsanitized peer hostname directly into a `shell_exec` command.
*   **How to exploit:** An authenticated admin user can enter a malicious string as the peer hostname, such as `; malicious_command`. This will execute arbitrary code on the server. For example: `example.com; rm -rf /`.
*   **How to defend:** Sanitize the input using `escapeshellarg()` before it is passed to the `shell_exec` function.
