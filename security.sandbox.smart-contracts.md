# Smart Contract Sandbox Security Review

This document provides a technical analysis of the sandboxing mechanism for smart contracts in PHPCoin.

## 1. Smart Contract Sandboxing Mechanism

Smart contracts in PHPCoin are executed in a sandboxed environment to prevent them from accessing sensitive resources or executing malicious code on the host system. The primary sandboxing mechanism is implemented in the `SmartContractEngine::isolateCmd` method in [`include/class/SmartContractEngine.php`](include/class/SmartContractEngine.php).

Smart contract code is packaged as a `.phar` (PHP Archive) file and executed using a `php` command with several security-related options:

```php
$exec_cmd = "CONFIG=$config $env php $debug_str -d disable_functions=$disable_functions ";
$exec_cmd.= " -d memory_limit=".SC_MEMORY_LIMIT." -d max_execution_time=".SC_MAX_EXEC_TIME." -d error_reporting=$error_reporting";
$exec_cmd.= " -d open_basedir=".self::getRunFolder().":".$allowed_files_list;
$exec_cmd.= " -f $cmd ";
```

The security features of this sandbox include:

*   **`disable_functions`**: This option disables a list of potentially dangerous PHP functions. The list of disabled functions is retrieved from the `get_sc_disable_functions` function in [`include/common.functions.php`](include/common.functions.php). The following functions are disabled:
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
    *   `ini_set`
    *   `getenv`
    *   `sleep`
    *   `set_time_limit`
    *   `error_reporting`
    *   `rand`
    *   `shuffle`
    *   `array_rand`
    *   `mt_rand`
    *   `uniqid`
    *   `date`
    *   `time`
    *   `microtime`
    *   `gettimeofday`
    *   `usleep`
    *   `getrandmax`
*   **`open_basedir`**: This option is critical for limiting file system access. The sandbox restricts the smart contract to a specific set of paths. The array of allowed paths is constructed in [`include/class/SmartContractEngine.php#L191-L208`](include/class/SmartContractEngine.php#L191-L208) and then passed to the `php` command at [`line 228`](include/class/SmartContractEngine.php#L228). The exact allowed paths are:
    *   The smart contract temporary run folder: `{ROOT}/tmp/sc`
    *   The entire smart contract class directory: `{ROOT}/include/class/sc/`
    *   Specific framework files required for operation, such as:
        *   `{ROOT}/chain_id`
        *   `{ROOT}/include/sc.inc.php`
        *   `{ROOT}/include/db.inc.php`
        *   `{ROOT}/include/common.functions.php`
        *   (conditional) `{ROOT}/include/coinspec.{chain_id}.inc.php`
*   **Resource Limits**: The sandbox also imposes limits on the execution time (`max_execution_time`) and memory usage (`memory_limit`) of the smart contract.

## 2. SQL Injection Vulnerability

**Severity:** Critical

**Analysis:**

The `query` method in `include/class/sc/SmartContractBase.php` is vulnerable to SQL injection. The method constructs a SQL query by directly concatenating a user-provided string into the query. This allows a malicious smart contract to execute arbitrary SQL, including `UPDATE` and `DELETE` statements.

The vulnerability is located in the `SmartContractBase::query` method in [`include/class/sc/SmartContractBase.php#L267-L287`](include/class/sc/SmartContractBase.php#L267-L287):

```php
static function query($address, $sql, $params =[]) {
    $db = SmartContractContext::$db;
    $final_sql="with s as (select ss.variable, ss.var_key, ss.var_value
           from (select s.sc_address, s.variable, ifnull(s.var_key, 'null') as var_key, max(s.height) as height
                 from smart_contract_state s
                 where s.sc_address = :sc_address
                 group by s.variable, s.var_key, s.sc_address) as last_vars
                    join smart_contract_state ss
                         on (ss.sc_address = last_vars.sc_address and ss.variable = last_vars.variable
                             and ifnull(ss.var_key, 'null') = last_vars.var_key and ss.height = last_vars.height))
                             select *
            from s
            where 1=1 ";
    $final_sql.= " $sql";
    $all_params = $params;
    $all_params[":sc_address"] = $address;
    $rows=$db->run($final_sql, $all_params);
    $list = [];
    foreach ($rows as $row) {
        $key = $row['var_key'];
        $val = $row['var_value'];
        $list[$key]=$val;
    }
    return $list;
}
```

A malicious smart contract could exploit this vulnerability by crafting a malicious SQL string and passing it to the `query` method. For example:

```php
$this->query($this->address, "; UPDATE accounts SET balance = 1000000 WHERE id = 'attacker_account_id'; --");
```

This would cause the `query` method to execute the malicious `UPDATE` statement, giving the attacker control over the node's database.

## 3. Advanced Sandbox Bypass Techniques

While the `disable_functions` directive is more comprehensive for smart contracts than for dapps, it is not foolproof. The backtick operator (`` ` ``) is also disabled as an alias for `shell_exec`. However, several bypass vectors remain.

### 3.1. Indirect Execution via Callback Functions

**Severity:** High

**Analysis:**
The `disable_functions` list for smart contracts does not include callback functions like `call_user_func()` or `call_user_func_array()`. These functions can be used to call other functions by their string name, which can allow an attacker to invoke a disabled function indirectly.

**Example Payload:**
```php
// The smart contract code contains this:
call_user_func('shell_exec', 'ls -la /');
```
This represents a significant vector for sandbox escape, as `call_user_func` acts as a proxy to execute the disabled `shell_exec` function.

### 3.2. File Manipulation

**Severity:** Medium

**Analysis:**
Standard file I/O functions such as `file_put_contents()`, `fopen()`, or `fwrite()` are not disabled. This could allow a smart contract to write files to the filesystem.

**Mitigation and Limitations:**
This attack is heavily mitigated by the `open_basedir` configuration. A smart contract can only write to the `/tmp/sc` directory. Unlike the Dapp environment, there is no direct web-accessible path to execute a PHP file written here, which makes a traditional "web shell" attack unlikely. However, the ability to write files could still be used to exhaust disk space or interfere with the operation of other smart contracts.

### 3.3. Other Execution Vectors

**Severity:** Medium

**Analysis:**
Several other functions that can lead to code or command execution are not included in the `disable_functions` list:

*   **`pcntl_exec()`**: Can be used to replace the current PHP process with another program.
*   **`dl()`**: Allows for loading arbitrary PHP extensions. This is a severe risk, mitigated only by the `open_basedir` restriction preventing the contract from accessing an uploaded extension file.
*   **`assert()`**: In certain configurations, `assert()` can be used for code execution.

### 3.4. Server-Side Request Forgery (SSRF)

**Severity:** Medium

**Analysis:**
While `curl_exec` is disabled, functions like `file_get_contents()` and `fsockopen()` are not. A smart contract could use these functions to make outbound network requests from the node, allowing an attacker to probe the node's internal network, access internal services, or exfiltrate data. The `open_basedir` setting does not prevent these outbound network requests.

## 4. Conclusion

The smart contract sandboxing mechanism in PHPCoin is more restrictive than the Dapp sandbox, but it is still critically flawed. The SQL injection vulnerability in the `SmartContractBase::query` method allows for a complete compromise of the node's database. Furthermore, the sandbox is vulnerable to several advanced bypass techniques that can undermine the `disable_functions` protection.

It is strongly recommended to remove the vulnerable `query` method and to implement a more robust security model that does not rely solely on a blacklist of disabled functions.
