# Smart Contract Security Audit

This document contains a security audit of the smart contract and dapp system.

## 1. Sandbox Escape via `dapps_exec`

**Severity:** Critical

**Description:**

The `dapps_exec` function provides a mechanism for a dapp to execute arbitrary PHP code on the node that is running it. This function is protected by a check, `Dapps::isLocal()`, which is intended to ensure that only "local" dapps can use it. However, if a user can be tricked into installing a malicious dapp, that dapp is considered "local" and can use `dapps_exec` to execute any code it wants, completely bypassing the sandbox.

**Attack Vector:**

1.  An attacker creates a malicious dapp. The dapp's code is very simple; it just needs to output a string like this:
    ```
    action:{"type":"dapps_exec","code":"file_put_contents('/tmp/pwned', 'success');"}
    ```
2.  The attacker convinces a node operator to install this dapp.
3.  When anyone visits the URL for the malicious dapp, the node executes the dapp's code in a sandbox.
4.  The dapp returns the malicious action string.
5.  The `Dapps::render()` method in `include/class/Dapps.php` receives this string and calls `Dapps::processAction()`.
6.  `Dapps::processAction()` checks if the dapp is local. Since it is, the check passes.
7.  The `eval()` function is called with the attacker's code, and the file `/tmp/pwned` is created on the node's filesystem.

**Proof of Concept:**

A malicious `index.php` file for a dapp could contain the following code:

```php
<?php
echo 'action:{"type":"dapps_exec","code":"file_put_contents(\'/tmp/pwned_by_dapp\', \'success\');"}';
?>
```

If this dapp is installed on a node, visiting its URL will create the file `/tmp/pwned_by_dapp` on the node's server.

**Recommendation:**

The `dapps_exec` function is extremely dangerous and should be removed entirely. There is no safe way to execute arbitrary code from a potentially untrusted source. If there is a legitimate need for dapps to interact with the host system, it should be done through a very narrow and well-defined API that does not involve executing arbitrary code.

## 2. SQL Injection in `SmartContractBase::query`

**Severity:** Critical

**Description:**

The `query` method in `include/class/sc/SmartContractBase.php` is vulnerable to SQL injection. This method allows a smart contract to execute a SQL query against the node's database, and it does so by directly concatenating a user-provided string into the query. This means that a malicious smart contract can execute any arbitrary SQL it wants, including `UPDATE` and `DELETE` statements.

**Attack Vector:**

1.  An attacker creates a malicious smart contract.
2.  The smart contract contains a method that calls the `query` method with a malicious SQL string. For example:
    ```php
    $this->query($this->address, "some_variable", $this->height, "; UPDATE accounts SET balance = 1000000 WHERE id = 'attacker_account_id'");
    ```
3.  The attacker executes this method through a transaction.
4.  The node executes the smart contract, which in turn executes the malicious SQL, and the attacker's account is credited with a million coins.

**Proof of Concept:**

A malicious smart contract could contain the following method:

```php
    /**
     * @SmartContractTransact
     */
    public function pwn() {
        $this->query($this->address, "foo", $this->height, "; UPDATE accounts SET balance = 1000000 WHERE id = 'attacker_account_id'");
    }
```

Executing this method would grant the attacker a large sum of money.

**Recommendation:**

The `query` method should be removed. It is too dangerous to allow smart contracts to execute arbitrary SQL. If there is a legitimate need for smart contracts to query the database, it should be done through a set of predefined, parameterized queries that do not allow for arbitrary SQL execution.

## 3. Analysis of Common Smart Contract Vulnerabilities

This section analyzes the smart contract system for common vulnerabilities such as reentrancy and integer overflows.

### Reentrancy

**Status:** Not Vulnerable

**Analysis:**

Reentrancy attacks are a common problem in smart contract platforms like Ethereum, where a contract can call another contract, which can then call back into the original contract before the first call is finished. This can lead to unexpected state changes and theft of funds.

The smart contract system in this project is **not vulnerable** to reentrancy attacks. The system processes transactions one at a time, in a single thread. This means that a smart contract can't be called again before its first invocation is complete. The `execSmartContract` and `callSmartContract` methods in `SmartContractBase.php` are the only ways for a smart contract to call another smart contract. These methods are synchronous and do not allow for reentrancy.

### Integer Overflows

**Status:** Not Vulnerable

**Analysis:**

Integer overflows occur when a mathematical operation results in a number that is too large to be stored in the available memory. This can lead to unexpected behavior and security vulnerabilities.

The smart contract system in this project is **not vulnerable** to integer overflows. The system is written in PHP, which uses 64-bit integers and automatically converts to floating-point numbers when an integer is too large. This means that integer overflows are not a concern.
