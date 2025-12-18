# SQL Injection

This document details SQL injection vulnerabilities.

## 1. SQL Injection in Smart Contracts

**- Vulnerability:** A critical SQL injection vulnerability exists in the `query()` method of the `SmartContractBase` class.
**- How it Works:** The `$sql` parameter, which can be fully controlled by a smart contract's code, is directly concatenated into a live SQL query. A malicious smart contract can be deployed that uses this method to execute arbitrary SQL, allowing it to read, modify, or delete any data in the node's database, including account balances.
**- Affected File:** `include/class/sc/SmartContractBase.php`
**- Severity:** Critical
