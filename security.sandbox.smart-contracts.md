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

This would cause the `query` method to execute the malicious `UPDATE` statement, giving the attacker control over the node's database. This vulnerability allows for the complete theft of funds and compromise of the blockchain node. An attacker can execute statements like `DROP TABLE smart_contract_state;` or `UPDATE accounts SET balance = 0 WHERE address != 'attacker';`, leading to a total loss of funds and node integrity.

## 3. Advanced Sandbox Bypass Techniques

While the `disable_functions` directive is more comprehensive for smart contracts than for dapps, it is not foolproof. The backtick operator (`` ` ``) is also disabled as an alias for `shell_exec`. However, as with other disabled functions, this can be trivially bypassed through the indirect execution techniques described below, rendering the blacklist ineffective. Several bypass vectors remain.

### 3.1. Indirect Execution via Callback Functions

**Severity:** Critical

**Analysis:**
The exclusion of `call_user_func()` and `call_user_func_array()` from the `disable_functions` list is a fundamental design flaw. It renders the entire function blacklist meaningless, allowing any smart contract to trivially bypass the sandbox and achieve Remote Code Execution (RCE).

**Example Payload:**
```php
// The smart contract code contains this:
call_user_func('shell_exec', 'ls -la /');
```
This represents a critical vector for sandbox escape, as `call_user_func` acts as a proxy to execute the disabled `shell_exec` function.

### 3.2. File Manipulation

**Severity:** Medium

**Analysis:**
Standard file I/O functions such as `file_put_contents()`, `fopen()`, or `fwrite()` are not disabled. This could allow a smart contract to write files to the filesystem.

**Mitigation and Limitations:**
This attack is heavily mitigated by the `open_basedir` configuration. A smart contract can only write to the `{ROOT}/tmp/sc` directory. Unlike the Dapp environment, there is no direct web-accessible path to execute a PHP file written here, which makes a traditional "web shell" attack unlikely. However, the ability to write files could still be used for a Denial-of-Service attack. For example, a malicious contract could continuously write small files to `/tmp/sc` with unpredictable names. This could fill the `/tmp` partition, causing the node's database, log files, or other critical system processes to fail. This attack is also a form of Denial-of-Service against other legitimate smart contracts that rely on the same temporary space.

### 3.3. Stream Wrapper Abuse

**Severity:** High

**Analysis:**
The sandbox does not restrict the use of PHP's stream wrappers, which can be used to bypass `open_basedir` and access the filesystem in unintended ways.

*   **`phar://`**: This wrapper can be used to access the contents of `.phar` files. A malicious actor could craft a `.phar` file containing a webshell or other malicious code, and then use the `phar://` wrapper to execute it. This is a well-known bypass for `open_basedir`.
*   **`php://`**: This wrapper provides access to various I/O streams. `php://filter` can be used to read local files, and `php://input` can be used to read raw POST data. This could be used to exfiltrate data or execute code.

### 3.4. Information Disclosure

**Severity:** High

**Analysis:**
Functions like `get_defined_constants()`, `constant()`, and access to global variables are not restricted, allowing a smart contract to leak highly sensitive information about the host environment, including credentials.

*   **`get_defined_constants()` and `constant()`**: These functions can be used to read the values of any defined constants. If the host application stores sensitive data like database passwords or API keys in constants, a smart contract can easily exfiltrate them.
*   **Global Variables**: Similarly, global variables like `$_CONFIG` or `$GLOBALS` may contain sensitive configuration data. A smart contract can access these variables and leak their contents.

**Example Payload:**
```php
// Leak database credentials stored in a constant
$db_password = constant('DB_PASSWORD');

// Leak credentials from the global config array
global $_CONFIG;
$db_user = $_CONFIG['db_user'];
$db_pass = $_CONFIG['db_pass'];
```

### 3.5. Reflection-Based Bypasses

**Severity:** High

**Analysis:**
The `ReflectionFunction` class can be used to invoke functions indirectly, which can be used to bypass the `disable_functions` directive. An attacker can create a `ReflectionFunction` object for a disabled function and then use the `invoke()` or `invokeArgs()` method to execute it.

**Example Payload:**
```php
$func = new ReflectionFunction('shell_exec');
$func->invoke('ls -la /');
```

### 3.6. Arbitrary Code Execution via `preg_replace()`

**Severity:** Critical

**Analysis:**
The `preg_replace()` function with the `/e` (evaluate) modifier is a powerful feature that can lead to remote code execution if used with user-supplied input. An attacker can craft a string that, when evaluated by `preg_replace()`, executes arbitrary PHP code.

**Example Payload:**
```php
preg_replace('/.*/e', 'shell_exec("ls -la /")', '');
```

### 3.7. Deserialization Vulnerabilities

**Severity:** Critical

**Analysis:**
The `unserialize()` function is not disabled, which can lead to object injection and arbitrary code execution. If an attacker can control the input to `unserialize()`, they can craft a serialized string that, when deserialized, creates an object of a class with a `__wakeup()` or `__destruct()` magic method. This magic method can then be used to execute arbitrary code.

**Example Payload:**
```php
class Evil {
    public $cmd;
    public function __destruct() {
        shell_exec($this->cmd);
    }
}
$evil = new Evil();
$evil->cmd = 'ls -la /';
echo serialize($evil);
```

### 3.8. Other Execution Vectors

**Severity:** Medium

**Analysis:**
Several other functions that can lead to code or command execution are not included in the `disable_functions` list:

*   **`pcntl_exec()`**: A more advanced and stealthy bypass. Unlike `shell_exec`, `pcntl_exec` replaces the entire PHP process with an external program. This bypasses PHP-level logging and security hooks, making it significantly harder to detect in a post-incident forensic analysis. It gives the attacker a clean, OS-level process to execute their commands.
*   **`dl()`**: Allows for loading arbitrary PHP extensions. This is a severe risk, mitigated only by the `open_basedir` restriction preventing the contract from accessing an uploaded extension file.
*   **`assert()`**: In certain configurations, `assert()` can be used for code execution. This is especially dangerous if the first argument is a string, as it will be evaluated as PHP code.
*   **`putenv()`**: This function can be used to set environment variables. An attacker could use this to influence the behavior of other programs or to escalate their privileges.
*   **`register_shutdown_function()`**: This function registers a function to be executed when the script finishes. An attacker could use this to execute code after the main script has finished, potentially bypassing some security checks.

### 3.9. Server-Side Request Forgery (SSRF)

**Severity:** Medium

**Analysis:**
While `curl_exec` is disabled, functions like `file_get_contents()` and `fsockopen()` are not. A smart contract could use these functions to make outbound network requests from the node, allowing an attacker to probe the node's internal network, access internal services, or exfiltrate data. The `open_basedir` setting does not prevent these outbound network requests. For example, if the node is hosted on a cloud provider like AWS, GCP, or Azure, an attacker could use `file_get_contents()` to access the instance metadata service (e.g., `http://169.254.169.254/latest/meta-data/iam/security-credentials/`). This could expose temporary IAM credentials, allowing the attacker to pivot and attack other services within the cloud environment.

## 4. Conclusion

The smart contract sandboxing mechanism in PHPCoin is fundamentally broken and insecure. The combination of a **Critical SQL injection** that compromises the node's database and **Critical-level sandbox bypasses** (notably via `call_user_func`) means that any deployed smart contract can achieve full Remote Code Execution.

**Consequences:**
*   An attacker can exfiltrate the node's private keys and wallet files, leading to the total theft of funds.
*   An attacker can wipe the node's file system (`rm -rf /`), destroying the node and causing network instability.
*   Because the contract code is public and the vulnerability is network-wide, a single malicious contract can be used to compromise **every node** that executes it.

The security model is not merely flawed; it is entirely ineffective. It is strongly recommended to **immediately halt all smart contract execution** until a complete architectural overhaul is implemented, replacing the blacklist-based sandbox with a robust, containerized environment and removing all opportunities for arbitrary code execution.

### Proposed whitelister

```
<?php

// include/security/SyntacticWhitelistLinter.php

class SyntacticWhitelistLinter
{
    // FACT 1: This is the explicit, complete list of ALLOWED functions.
    // If a function's name is not in this array, the linter will flag it as a violation.
    private const ALLOWED_FUNCTIONS = [
        'strlen', 'strpos', 'substr', 'trim', 'explode', 'implode',
        'count', 'array_merge', 'array_keys', 'is_null', 'is_int', 'isset',
        'self::getState', 'self::setState', 'self::getTxValue', 'self::emitEvent',
    ];

    // FACT 2: This is the explicit, complete list of ALLOWED language constructs.
    // These are identified by their PHP token constants.
    // If a token ID (e.g., T_EVAL) is not in this array, the linter will flag it as a violation.
    private const ALLOWED_CONSTRUCTS = [
        T_ECHO, T_PRINT, T_IF, T_ELSE, T_FOR, T_FOREACH, T_WHILE, T_DO,
        T_RETURN, T_BREAK, T_CONTINUE, T_SWITCH, T_CASE, T_CLASS, T_FUNCTION,
        T_NEW, T_INSTANCEOF, T_TRY, T_CATCH, T_THROW,
        T_ISSET, T_EMPTY, T_UNSET, T_LIST, T_ARRAY, T_STRING_CAST, T_BOOL_CAST,
    ];

    // FACT 3: This is the explicit, complete list of ALLOWED operators.
    // If an operator character is not in this array, the linter will flag it.
    private const ALLOWED_OPERATORS = [
        '=', '=>', '->', '::', ';', ',', '.', '[', ']', '{', '}', '(', ')',
        '+', '-', '*', '/', '%', '&&', '||', '!', '++', '--',
        '==', '===', '!=', '!==', '>', '<', '>=', '<=', '.=', '+=', '-=', '*=', '/=', '%='
    ];

    // FACT 4: This function analyzes a file and returns a list of all violations found.
    public static function analyze(string $filePath): array
    {
        $violations = [];
        $sourceCode = file_get_contents($filePath);
        $tokens = token_get_all($sourceCode);

        foreach ($tokens as $token) {
            if (is_array($token)) {
                $tokenType = $token[0];
                // Check functions
                if ($tokenType === T_STRING) {
                    $functionName = $token[1];
                    $nextToken = next($tokens);
                    if ($nextToken === '(') {
                        if (!in_array($functionName, self::ALLOWED_FUNCTIONS, true)) {
                            $violations[] = ['line' => $token[2], 'type' => 'function', 'element' => $functionName];
                        }
                    }
                // Check constructs
                } elseif (!in_array($tokenType, self::ALLOWED_CONSTRUCTS, true) && !in_array($tokenType, [T_WHITESPACE, T_COMMENT, T_OPEN_TAG, T_CLOSE_TAG])) {
                    $violations[] = ['line' => $token[2], 'type' => 'construct', 'element' => token_name($tokenType)];
                }
            } else { // Check operators
                $operator = $token;
                if ($operator === '`') {
                    $violations[] = ['type' => 'operator', 'element' => 'backtick'];
                } elseif (!in_array($operator, self::ALLOWED_OPERATORS, true)) {
                    $violations[] = ['type' => 'operator', 'element' => $operator];
                }
            }
        }
        return $violations;
    }
}
```
