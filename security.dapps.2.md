# Dapp Security Review 2

This document contains the findings of a security review of the dapp system.

## 1. Sandbox Escape via `dapps_exec` and Dapp Propagation

**Severity:** Critical

**Analysis:**

The `dapps_exec` function in `include/class/Dapps.php` allows a "local" dapp to execute arbitrary PHP code on the node, completely bypassing the sandbox. This is a critical vulnerability on its own, but its severity is significantly amplified by the "propagate dapps" feature.

The "propagate dapps" feature, when enabled, allows a node to automatically distribute its local dapps to all of its peers. This means that if a node operator is tricked into installing a malicious dapp, that dapp will be automatically propagated to all other nodes on the network that have enabled this feature. This could lead to a widespread compromise of the network.

The setting to enable or disable this feature is `dapps_disable_auto_propagate` in the node's configuration file. By default, this feature is **disabled**, which mitigates the risk of a widespread attack. However, any node operator who enables this feature is putting their node and the entire network at risk.

**Conclusion:**

The combination of the `dapps_exec` vulnerability and the "propagate dapps" feature represents a critical threat to the network. While the risk is mitigated by the fact that dapp propagation is disabled by default, it is still a significant concern.

## 2. SQL Injection in `SmartContractBase::query`

**Severity:** Critical

**Analysis:**

The `query` method in `include/class/sc/SmartContractBase.php` is vulnerable to SQL injection. The method constructs a SQL query by directly concatenating a user-provided string into the query. This allows a malicious smart contract to execute arbitrary SQL, including `UPDATE` and `DELETE` statements.

The investigation confirmed that the database driver's `run` method uses PDO's `prepare` and `execute` methods, which would normally prevent SQL injection. However, because the user-provided SQL is concatenated into the main query string *before* it is passed to the `run` method, the prepared statement is created with the malicious SQL already in it. This renders the protection offered by prepared statements ineffective.

### Example Attack

A malicious smart contract could contain the following method:

```php
/**
 * @SmartContractTransact
 */
public function exploit() {
    $malicious_sql = "; UPDATE accounts SET balance = 1000000 WHERE id = 'attacker_account_id'; --";
    $this->query($this->address, "some_variable", $this->height, $malicious_sql);
}
```

When this method is executed, the `query` method in `SmartContractBase.php` will construct the following SQL query:

```sql
select s.var_key, s.var_value from smart_contract_state s
where s.sc_address = :sc_address and s.variable = :variable and s.height <= :height ; UPDATE accounts SET balance = 1000000 WHERE id = 'attacker_account_id'; -- order by s.var_key
```

### Why Prepared Statements Are Not Effective Here

A prepared statement works by separating the SQL query's structure from the data that is being passed into it. The database server first receives the query structure with placeholders (e.g., `?` or `:name`), and then it receives the data for those placeholders. This prevents the data from being interpreted as part of the SQL command.

In this case, the vulnerability occurs because the untrusted input from the smart contract (`$malicious_sql`) is concatenated directly into the main SQL string *before* the `prepare` step. The `DB::run` method receives a single, already-compromised string. When `PDO::prepare` is called on this string, the database server sees two complete and valid SQL statements, and it will execute both of them.

The protection of prepared statements is completely bypassed because the malicious SQL is not treated as data; it is part of the SQL command itself.

### Proposed Fix

To mitigate this vulnerability at the database level, the `run()` function in `include/db.inc.php` should be modified to reject any input that contains multiple SQL statements. This prevents the query stacking technique used in the exploit.

The following code should be added to the beginning of the `run()` function:

```php
    public function run($sql, $bind = "", $param=true)
    {
        // Check for multiple statements to prevent SQL injection via query stacking
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        if (count($statements) > 1) {
            $this->error = "Multiple SQL statements are not allowed.";
            $this->debug();
            return false;
        }

        $this->sql = trim($sql);
        // ... (rest of the function)
```

This change inspects the incoming SQL string and checks if it contains more than one statement separated by a semicolon. If it does, the execution is aborted. This provides a centralized defense against this specific type of SQL injection attack without requiring changes to the smart contract API.

**Conclusion:**

The SQL injection vulnerability in `SmartContractBase::query` is a critical vulnerability that allows a malicious smart contract to take complete control of the node's database. This vulnerability should be addressed immediately by implementing the proposed fix in the `DB::run` method.
