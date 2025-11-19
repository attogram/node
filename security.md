# Security Vulnerability Report

This report details critical security vulnerabilities found in the codebase. Each vulnerability is described with an explanation of the issue, how it can be exploited, and a recommended defense.

---

### 1. Lack of Network Time-Synchronization Defenses

*   **What it is:** The network's sole defense against time-based attacks is a node's local system clock. There is no mechanism to ensure that all nodes on the network have a synchronized time.
*   **Analysis:**
    *   **No Median Time Past (MTP):** The codebase does not implement a Median Time Past (MTP) check, which is the standard defense for preventing "Timewarp" attacks.
    *   **No NTP Client:** The codebase does not contain an NTP or SNTP client, nor does it make any attempt to connect to external time servers to synchronize the local clock.
    *   **No Peer-to-Peer Time Check:** The codebase does not include any mechanism for nodes to query each other for the time or to establish any form of time consensus.
*   **Conclusion:** The network's complete reliance on the local clock makes the "Future-Push" and "Network-Split" vulnerabilities viable and severe.

---

### 2. Consensus Exploit: "Timewarp" Attack

*   **What it is:** The "Elapsed Proof of Work" (EPOW) consensus mechanism's difficulty calculation is based on the time elapsed between blocks. This can be manipulated by falsifying a block's timestamp.
*   **How to exploit:** A miner solves a block, waits for a period of time, and then broadcasts it with a timestamp set far in the future (up to the 30-second limit). This artificially increases the `$elapsed` time, which lowers the mining `target`, making the block valid when it otherwise would not have been.
*   **How to defend:** Implement a **Median Time Past (MTP)** check. A new block's timestamp must be greater than the median timestamp of the last 11 blocks. This prevents large timestamp manipulations.

---

### 3. Consensus Exploit: "Future-Push" Attack

*   **What it is:** A specific "slip time" attack that exploits the 30-second future timestamp allowance to validate a block that was solved too quickly.
*   **How to exploit:** A miner solves a block in only a few seconds, resulting in a `hit` that is too low for the high difficulty `target`. The miner then sets the block's timestamp to `time() + 29s`. This inflates the `$elapsed` time, lowers the `target`, and makes their previously invalid block valid.
*   **How to defend:** Drastically reduce the future-dating window. A value of 2-3 seconds is sufficient for minor clock drift without being large enough to be gameable.

---

### 4. Consensus Exploit: "Network-Split" Attack

*   **What it is:** Another "slip time" attack that uses the 30-second future timestamp allowance to intentionally cause a network fork.
*   **How to exploit:** An attacker broadcasts a block with a timestamp of `time() + 29s`. Nodes with accurate clocks will accept it. However, nodes whose system clocks are running more than 30 seconds slow will reject the block as being too far in the future. This disagreement on block validity splits the network.
*   **How to defend:** In addition to reducing the future-dating window, the node should periodically check its clock against a trusted Network Time Protocol (NTP) server to prevent significant clock drift.

---

### 5. Remote Code Execution: Command Injection

*   **What it is:** The "Add Peer" functionality in the admin panel (`web/apps/admin/index.php`) passes an unsanitized peer hostname directly into a `shell_exec` command.
*   **How to exploit:** An authenticated admin user can enter a malicious string as the peer hostname, such as `; malicious_command`. This will execute arbitrary code on the server. For example: `example.com; rm -rf /`.
*   **How to defend:** Sanitize the input using `escapeshellarg()` before it is passed to the `shell_exec` function.

---

### 6. Client-Side Exploit: Cross-Site Scripting (XSS)

*   **What it is:** The transaction `message` field is printed directly to the HTML page without any output escaping in multiple locations.
*   **How to exploit:** An attacker can create a transaction with malicious HTML, such as `<script>alert('XSS')</script>`, in the `message` field. When any user views this transaction on an affected page, the script will execute in their browser, potentially stealing session cookies or performing other malicious actions.
*   **Affected Files:**
    *   `web/apps/explorer/tx.php`
    *   `web/apps/explorer/address.php`
    *   `web/apps/explorer/mempool.php`
    *   `web/apps/admin/tabs/mempool.php`
*   **How to defend:** Escape the output using `htmlspecialchars()` before printing the message content to the page. For example: `<?php echo htmlspecialchars($tx['message']) ?>`.

---

### 7. Session Exploit: Cross-Site Request Forgery (CSRF)

*   **What it is:** The admin panel (`web/apps/admin/index.php`) is vulnerable to CSRF because it lacks anti-CSRF tokens and uses `GET` requests for state-changing actions.
*   **How to exploit:** An attacker tricks a logged-in admin into visiting a malicious website. This site can contain hidden forms or image tags that automatically submit requests to the admin panel, performing actions like deleting all peers (`/apps/admin/?action=delete_peers`) without the admin's knowledge or consent.
*   **How to defend:**
    1.  Implement anti-CSRF tokens (synchronizer tokens) in all forms and state-changing requests.
    2.  Convert all state-changing actions that use `GET` requests to use `POST` requests instead.

---

### 8. Remote Code Execution: Dapp Sandbox Escape via Insecure Deserialization

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
