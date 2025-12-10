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

*   **`disable_functions`**: This option disables a list of potentially dangerous PHP functions that could be used to execute shell commands, read or modify system files, or establish network connections. The disabled functions are: `exec`, `passthru`, `shell_exec`, `system`, `proc_open`, `popen`, `curl_exec`, `curl_multi_exec`, `parse_ini_file`, `show_source`, `set_time_limit`, `ini_set`.
*   **`open_basedir`**: This option restricts the file system access of the dapp to a specific set of directories. The allowed directories are the dapp's own directory, a temporary directory, and a list of allowed files.
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

## 3. Conclusion

The dapp sandboxing mechanism in PHPCoin provides a basic level of protection, but it is completely undermined by the `dapps_exec`, `dapps_exec_fn`, and `dapps_sql` actions. These actions provide a direct and easy way for a dapp to escape the sandbox and compromise the host system.

It is strongly recommended to remove these actions or to implement a more robust sandboxing mechanism that does not rely on a blacklist of disabled functions.
