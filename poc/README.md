# Proof-of-Concept for Pajax RCE

This directory contains a proof-of-concept for the RCE vulnerability in the `Pajax` class.

## Files

*   `malicious_dapp/exploit.php`: This is the malicious script that, when executed by a node, will trigger the RCE.

## How to Use

1.  **Simulate a Compromised Dapp:**
    *   On a node that you control, navigate to the `dapps/` directory.
    *   Create a new directory for your dapp, using a valid address as the directory name (e.g., `38_your_dapp_address`).
    *   Copy the `exploit.php` script from `poc/malicious_dapp/` into this new directory.
    *   Ensure your node is configured to host this dapp.

2.  **Trigger the Exploit:**
    *   From any machine, use `curl` to make a web request to the `exploit.php` script on the node:
        ```bash
        curl http://<node-ip>/dapps.php?url=<dapp-id>/exploit.php
        ```
    *   Replace `<node-ip>` with the IP address of the compromised node and `<dapp-id>` with the address you used for the directory name.

3.  **Verify:**
    *   SSH into the compromised node.
    *   Check for the existence of the file `/tmp/pwned_dapp`. If it exists, the exploit was successful.
