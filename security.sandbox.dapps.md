# Dapp Sandbox Security Review

This document provides a technical analysis of the sandboxing mechanism for dapps in PHPCoin.

## 1. Dapp Sandboxing Mechanism

Dapps in PHPCoin are executed in a sandboxed environment to prevent them from accessing sensitive resources or executing malicious code on the host system. The primary sandboxing mechanism is implemented in the `Dapps::render` method in [`include/class/Dapps.php`](include/class/Dapps.php).

The sandbox is created by executing the dapp's PHP code using a `php` command with several security-related options:

```php
$cmd = "php $debug -d disable_functions=exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source,set_time_limit,ini_set" .
    " -d open_basedir=" . $dapps_dir . "/$dapps_id:".$tmp_dir.":".$allowed_files_list .
    " -d max_execution_time=5 -d memory_limit=128M " .
    " -d auto_prepend_file=$functions_file $file 2>&1";
```

The security features of this sandbox include:

*   **`disable_functions`**: This option disables a list of potentially dangerous PHP functions. The following functions are disabled:
    *   `exec`
    *   `passthru`
    *   `shell_exec`
    *   `system`
    *   `proc_open`
    *   `popen`
    *   `curl_exec`
    *   `curl_multi_exec`
    *   `parse_ini_file`
    *   `show_source`
    *   `set_time_limit`
    *   `ini_set`
*   **`open_basedir`**: This option is critical for limiting file system access. The sandbox restricts the Dapp to a specific set of paths. The array of allowed paths is constructed in [`include/class/Dapps.php#L397-L417`](include/class/Dapps.php#L397-L417) and then passed to the `php` command at [`line 421`](include/class/Dapps.php#L421). The exact allowed paths are:
    *   The Dapp's root directory: `{ROOT}/dapps/{dapps_id}`
    *   A temporary Dapp directory: `{ROOT}/tmp/dapps`
    *   A temporary sessions directory: `{ROOT}/tmp/sessions`
    *   Specific framework files required for operation, such as:
        *   `{ROOT}/chain_id`
        *   `{ROOT}/include/dapps.functions.php`
        *   `{ROOT}/include/common.functions.php`
        *   `{ROOT}/include/coinspec.inc.php`
        *   `{ROOT}/include/class/CommonSessionHandler.php`
        *   (conditional) `{ROOT}/include/coinspec.{chain_id}.inc.php`
        *   (conditional, for local dapps) `{ROOT}/config/dapps.config.inc.php`
*   **Resource Limits**: The sandbox also imposes limits on the execution time (`max_execution_time=5`) and memory usage (`memory_limit=128M`) of the dapp.

## 2. Sandbox Escape Vulnerabilities

Despite the sandboxing measures, there are several critical vulnerabilities that allow a dapp to escape the sandbox and execute arbitrary code on the host system. These vulnerabilities are implemented as "actions" that a dapp can trigger.

### 2.1. `dapps_exec`

**Severity:** Critical

**Analysis:**

The `dapps_exec` action allows a local dapp to execute arbitrary PHP code on the node, completely bypassing the sandbox. This vulnerability is located in the `Dapps::processAction` method in [`include/class/Dapps.php#L484-L487`](include/class/Dapps.php#L484-L487):

```php
if($actionObj['type']=="dapps_exec" && self::isLocal($dapps_id)) {
    $code = $actionObj['code'];
    eval($code);
    exit;
}
```

If a dapp is running locally (`self::isLocal($dapps_id)`), it can send an action of type `dapps_exec` with a `code` parameter. The `eval()` function then executes the provided code without any sandboxing.

### 2.2. `dapps_exec_fn`

**Severity:** Critical

**Analysis:**

Similar to `dapps_exec`, the `dapps_exec_fn` action allows a local dapp to execute a function from a specific file on the host system. This vulnerability is also located in the `Dapps::processAction` method in [`include/class/Dapps.php#L488-L498`](include/class/Dapps.php#L488-L498):

```php
if($actionObj['type']=="dapps_exec_fn" && self::isLocal($dapps_id)) {
    $fn_name = $actionObj['fn_name'];
    $params = $actionObj['params'];
    $dapps_fn_file = ROOT . "/include/dapps.local.inc.php";
    if(!file_exists($dapps_fn_file)) {
        die("Dapps local functions file not exists");
    }
        require_once $dapps_fn_file;
    if(!function_exists($fn_name)) {
        die("Called function $fn_name not exists");
    }
    call_user_func($fn_name, ...$params);
    exit;
}
```

This allows a local dapp to call any function defined in `include/dapps.local.inc.php` with arbitrary parameters.

### 2.3. `dapps_sql`

**Severity:** Critical

**Analysis:**

The `dapps_sql` action allows a dapp to execute an arbitrary SQL query on the node's database. This vulnerability is located in the `Dapps::processAction` method in [`include/class/Dapps.php#L516-L522`](include/class/Dapps.php#L516-L522):

```php
if($actionObj['type']=="dapps_sql") {
    $query = $actionObj['query'];
    $params = $actionObj['params'];
    global $db;
    $rows = $db->select($query, $params);
    echo json_encode($rows);
    exit;
}
```

This gives a dapp full control over the node's database, allowing it to read, modify, or delete any data.

## 3. Advanced Sandbox Bypass Techniques

While the `disable_functions` directive provides a baseline level of security, it is not foolproof. An attacker with the ability to control the code within a Dapp can employ several advanced techniques to bypass these restrictions. The backtick operator (`` ` ``), an alias for `shell_exec`, is also disabled.

### 3.1. Indirect Execution via Callback Functions

**Severity:** Critical

**Analysis:**
The exclusion of `call_user_func()` and `call_user_func_array()` from the `disable_functions` list is a fundamental design flaw. It renders the entire function blacklist meaningless, allowing any remote dapp to trivially bypass the sandbox and achieve Remote Code Execution (RCE).

**Example Payload:**
```php
// The Dapp code contains this:
call_user_func('shell_exec', 'ls -la /');
```
In this scenario, `call_user_func` itself is allowed, but it is used as a proxy to execute the disabled `shell_exec` function. This represents a critical vector for sandbox escape.

### 3.2. Web Shell via File Manipulation

**Severity:** Critical

**Analysis:**
The `disable_functions` directive does not block standard file I/O functions such as `file_put_contents()`, `fopen()`, or `fwrite()`. This allows an attacker to write a new PHP file (a "web shell") to a location on the server.

**Example Payload:**
```php
$shell_code = '<?php system($_GET["cmd"]); ?>';
file_put_contents('shell.php', $shell_code);
```

**Mitigation and Limitations:**
The effectiveness of this attack is significantly limited by the `open_basedir` configuration. An attacker can only write the web shell to a directory that is explicitly whitelisted. In this Dapp sandbox, the only user-writable directories are the Dapp's own directory (`{ROOT}/dapps/{dapps_id}`) and the temporary directories (`{ROOT}/tmp/dapps`, `{ROOT}/tmp/sessions`). While this prevents writing to critical system directories, an attacker can write a persistent web shell. This provides a lasting backdoor, allowing the attacker to repeatedly access the server, steal data, and execute commands long after the initial dapp execution is complete. This is a catastrophic persistence mechanism.

### 3.3. Other Execution Vectors

**Severity:** Medium

**Analysis:**
Several other functions that can lead to code or command execution are not included in the `disable_functions` list:

*   **`pcntl_exec()`**: A more advanced bypass that replaces the entire PHP process with another program. This is more stealthy than `shell_exec` as it bypasses PHP-level logging and security hooks, executing the command directly at the operating system level.
*   **`dl()`**: Allows for loading arbitrary PHP extensions (`.so` or `.dll` files). If an attacker could somehow upload a malicious extension (again, limited by `open_basedir`), they could load it and execute native code, completely bypassing all PHP-level restrictions.
*   **`assert()`**: In certain configurations, `assert()` can be used for code execution.

### 3.4. Server-Side Request Forgery (SSRF)

**Severity:** Medium

**Analysis:**
While `curl_exec` is disabled, functions like `file_get_contents()` and `fsockopen()` are not. These can be used with URL wrappers (e.g., `http://`, `ftp://`) to initiate requests from the server. This could allow an attacker to probe the node's internal network, access internal services, or exfiltrate data. The `open_basedir` setting does not prevent these outbound network requests.

## 4. Conclusion

The Dapp platform's security is compromised by two distinct and critical issues.

First is the **intentional inclusion of sandbox-bypassing "actions"** (`dapps_exec`, `dapps_sql`, etc.) for local dapps. These are not bugs, but rather a dangerous design choice that grants arbitrary code execution and database control, effectively creating a backdoor. The only remediation for this is the complete removal of these features.

Second is the **failure of the sandbox implementation itself**. The exclusion of `call_user_func()` from the `disable_functions` list is a fundamental flaw that nullifies the entire security model, allowing any remote dapp to trivially bypass the function blacklist. This, combined with the ability to create persistent web shells, renders the sandbox ineffective even for non-local dapps.

It is strongly recommended to remove the "action" backdoors and to completely re-architect the sandboxing mechanism to be based on a "least privilege" principle rather than an easily-bypassed function blacklist.
