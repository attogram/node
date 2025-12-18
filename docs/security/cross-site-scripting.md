# Cross-Site Scripting (XSS)

This document details cross-site scripting (XSS) vulnerabilities.

## 1. Stored XSS in Transaction Message Field

*   **What it is:** The transaction `message` field is printed directly to the HTML page without any output escaping in multiple locations.
*   **How to exploit:** An attacker can create a transaction with malicious HTML, such as `<script>alert('XSS')</script>`, in the `message` field. When any user views this transaction on an affected page, the script will execute in their browser, potentially stealing session cookies or performing other malicious actions.
*   **Affected Files:**
    *   `web/apps/explorer/tx.php`
    *   `web/apps/explorer/address.php`
    *   `web/apps/explorer/mempool.php`
    *   `web/apps/admin/tabs/mempool.php`
*   **How to defend:** Escape the output using `htmlspecialchars()` before printing the message content to the page. For example: `<?php echo htmlspecialchars($tx['message']) ?>`.

## 2. Stored XSS in Transaction Data Field

*   **Attack Vector:**
    1.  **Craft Malicious Payload:** The attacker creates a JavaScript payload (e.g., `<script>alert('XSS');</script>`) designed to steal session cookies, private keys from a browser wallet, or perform other malicious actions.
    2.  **Submit Transaction:** The attacker embeds this payload into the `data` field of a transaction and submits it to the network.
    3.  **Store on Blockchain:** The transaction is mined and the malicious payload is permanently stored on the blockchain.
    4.  **View Transaction:** A victim browses the block explorer and views the details of the malicious transaction.
    5.  **Execute Payload:** The web page at `web/apps/explorer/tx.php` directly renders the content of the `data` field to the HTML without sanitization:
        ```php
        <td style="word-break: break-all"><?php echo $tx['data'] ?></td>
        ```
    6.  **Compromise Victim:** The victim's browser executes the malicious script in the context of the trusted block explorer domain, leading to the compromise of their account or data.
*   **Mitigations:** All user-controllable data that is rendered in an HTML context must be properly escaped. The fix for this vulnerability is to use the `htmlspecialchars()` function to convert special characters into their HTML entity equivalents, preventing the browser from interpreting the data as code.

    The vulnerable line in `web/apps/explorer/tx.php` should be changed to:
    ```php
    <td style="word-break: break-all"><?php echo htmlspecialchars($tx['data']) ?></td>
    ```
