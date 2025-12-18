# Local File Inclusion (LFI)

## 1. LFI in Pajax Class

**- Vulnerability:** A local file inclusion (LFI) vulnerability exists in the `Pajax` class.
**- How it Works:** The `processAjax` method in `include/class/Pajax.php` uses user-supplied data from the `HTTP_P_AJAX` header to construct a file path for a `require` statement. The `$class` variable is taken directly from the decoded JSON data without any sanitization. An attacker can manipulate this variable to traverse the directory and include arbitrary PHP files from the server's filesystem.
**- Attack Vector:** An attacker can send a specially crafted `HTTP_P_AJAX` header containing a payload like `../../../../../../etc/passwd` to include sensitive files.
**- Affected File:** `include/class/Pajax.php`
**- Severity:** High
