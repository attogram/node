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
*   **`open_basedir`**: This option is critical for limiting file system access. The sandbox restricts the Dapp to a specific set of paths constructed in [`include/class/Dapps.php`](include/class/Dapps.php). The exact allowed paths are:
    *   The Dapp's root directory: `/dapps/{dapps_id}`
    *   A temporary Dapp directory: `/tmp/dapps`
    *   A temporary sessions directory: `/tmp/sessions`
    *   Specific framework files required for operation, such as:
        *   `/chain_id`
        *   `/include/dapps.functions.php`
        *   `/include/common.functions.php`
        *   `/include/coinspec.inc.php`
        *   `/include/class/CommonSessionHandler.php`
        *   (conditional) `/include/coinspec.{chain_id}.inc.php`
        *   (conditional, for local dapps) `/config/dapps.config.inc.php`
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

**Severity:** High

**Analysis:**
The `disable_functions` list does not include callback functions like `call_user_func()` or `call_user_func_array()`. These functions can be used to call other functions by their string name. If an attacker can control the arguments passed to a callback function, they can invoke a disabled function indirectly, bypassing the security check.

**Example Payload:**
```php
// The Dapp code contains this:
call_user_func('shell_exec', 'ls -la /');
```
In this scenario, `call_user_func` itself is allowed, but it is used as a proxy to execute `shell_exec`, which is disabled. This represents a significant vector for sandbox escape.

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
The effectiveness of this attack is significantly limited by the `open_basedir` configuration. An attacker can only write the web shell to a directory that is explicitly whitelisted. In this Dapp sandbox, the only user-writable directories are the Dapp's own directory (`/dapps/{dapps_id}`) and the temporary directories (`/tmp/dapps`, `/tmp/sessions`). While this prevents writing to critical system directories, an attacker could still write a shell and attempt to execute it by accessing its URL (e.g., `http://node.com/dapps/{dapps_id}/shell.php?cmd=id`), potentially executing commands with the web server's permissions.

### 3.3. Other Execution Vectors

**Severity:** Medium

**Analysis:**
Several other functions that can lead to code or command execution are not included in the `disable_functions` list:

*   **`pcntl_exec()`**: Can be used to replace the current PHP process with another program.
*   **`dl()`**: Allows for loading arbitrary PHP extensions (`.so` or `.dll` files). If an attacker could somehow upload a malicious extension (again, limited by `open_basedir`), they could load it and execute native code, completely bypassing all PHP-level restrictions.
*   **`assert()`**: In certain configurations, `assert()` can be used for code execution.

### 3.4. Server-Side Request Forgery (SSRF)

**Severity:** Medium

**Analysis:**
While `curl_exec` is disabled, functions like `file_get_contents()` and `fsockopen()` are not. These can be used with URL wrappers (e.g., `http://`, `ftp://`) to initiate requests from the server. This could allow an attacker to probe the node's internal network, access internal services, or exfiltrate data. The `open_basedir` setting does not prevent these outbound network requests.

## 4. Conclusion

The dapp sandboxing mechanism in PHPCoin provides a basic level of protection, but it is completely undermined by the `dapps_exec`, `dapps_exec_fn`, and `dapps_sql` actions. These actions provide a direct and easy way for a dapp to escape the sandbox and compromise the host system. Furthermore, the sandbox is vulnerable to several advanced bypass techniques that render the `disable_functions` directive ineffective.

It is strongly recommended to remove the sandbox-escape actions and to implement a more robust security model that does not rely solely on a blacklist of disabled functions.
