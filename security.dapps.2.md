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

**Conclusion:**

The SQL injection vulnerability in `SmartContractBase::query` is a critical vulnerability that allows a malicious smart contract to take complete control of the node's database. This vulnerability should be addressed immediately by removing the `query` method or by implementing a safe, parameterized query system.
