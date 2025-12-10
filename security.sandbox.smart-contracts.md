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

*   **`disable_functions`**: This option disables a list of potentially dangerous PHP functions. The list of disabled functions is retrieved from the `get_sc_disable_functions` function in [`include/common.functions.php`](include/common.functions.php), and includes: `exec`, `passthru`, `shell_exec`, `system`, `proc_open`, `popen`, `curl_exec`, `curl_multi_exec`, `parse_ini_file`, `show_source`, `ini_set`, `getenv`, `sleep`, `set_time_limit`, `error_reporting`, `rand`, `shuffle`, `array_rand`, `mt_rand`, `uniqid`, `date`, `time`, `microtime`, `gettimeofday`, `sleep`, `usleep`, `getrandmax`.
*   **`open_basedir`**: This option restricts the file system access of the smart contract to a specific set of directories. The allowed directories are a temporary directory and a list of allowed files.
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

## 3. Conclusion

The smart contract sandboxing mechanism in PHPCoin provides a reasonable level of protection against a variety of attacks. However, the SQL injection vulnerability in the `SmartContractBase::query` method is a critical flaw that completely undermines the security of the sandbox.

It is strongly recommended to remove the `query` method or to refactor it to use a safe, parameterized query system that does not allow for the execution of arbitrary SQL.
