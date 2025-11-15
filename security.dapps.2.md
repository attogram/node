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

The `query` method should be refactored to prevent arbitrary SQL execution. Instead of accepting a raw SQL string, it should only accept a structured set of conditions, which can then be safely converted into a parameterized query. This eliminates the possibility of injection.

Here is an example of a refactored `query` method:

```php
    static function query($address, $filters = [], $params = []) {
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

        $all_params = $params;
        $all_params[":sc_address"] = $address;

        // Safely build WHERE clauses from a structured filter array
        foreach ($filters as $key => $value) {
            // Use a whitelist to prevent injection in column names
            if (!in_array($key, ['variable', 'var_key', 'var_value'])) {
                throw new Exception("Invalid filter key provided: $key");
            }
            // Add the condition to the SQL and the value to the parameters array
            $final_sql .= " AND " . $key . " = :" . $key . "_filter";
            $all_params[":" . $key . "_filter"] = $value;
        }

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

This revised function changes the API so that smart contracts can no longer pass arbitrary SQL. Instead, they would provide a simple key-value array for filtering, like `['variable' => 'owner']`, which is then safely converted into a prepared statement.

**Conclusion:**

The SQL injection vulnerability in `SmartContractBase::query` is a critical vulnerability that allows a malicious smart contract to take complete control of the node's database. This vulnerability should be addressed immediately by removing the `query` method or by implementing a safe, parameterized query system as proposed above.
