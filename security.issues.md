# PHPCoin Security Issues Summary

This document summarizes the major security vulnerabilities discovered during a security audit.

## Part 1: Consensus and Reward System Vulnerabilities

### 1. Reward Redirection (Miner & Staker)

**- Vulnerability:** A malicious block generator can steal the rewards intended for miners and stakers.
**- How it Works:** The block validation logic correctly checks the *amount* of a miner or staker reward transaction, but it fails to verify the *destination*. A generator can create a block that correctly identifies the miner/staker in the header, but then create a reward transaction that sends the funds to an address they control. Other nodes will accept this block as valid.
**- Affected Files:** `web/mine.php`, `include/class/Transaction.php` (specifically `checkRewards()`)
**- Severity:** High

### 2. Illegitimate Stake Reward Forgery

**- Vulnerability:** A malicious block generator who is also an *eligible staker* can forge a stake reward transaction for themselves, even if they were not the legitimate, consensus-determined winner for that block.
**- How it Works:** When validating a stake reward, the network checks that the recipient is an *eligible* staker (i.e., meets the balance and maturity requirements), but it **does not** re-run the deterministic winner selection algorithm (`Account::getStakeWinner()`) to confirm the recipient was the *correct* winner for that specific block. This allows any eligible staker who is also generating a block to illegitimately claim the stake reward.
**- Affected Files:** `include/class/Transaction.php` (specifically `checkRewards()`)
**- Severity:** High

### 3. Masternode Winner Selection Manipulation (Collusion Attack)

**- Vulnerability:** A malicious block generator can collude with any other verified masternode to bypass the deterministic winner selection process and assign them the block reward, even when they are not the legitimate winner.
**- How it Works:** Similar to the staking vulnerability, the block validation logic (`Masternode::verifyBlock()`) does not re-run the deterministic winner selection algorithm (`Masternode::getWinner()`). It only checks that the masternode specified in the block header is a valid, verified masternode and that its signature on the block is correct. This allows a generator to ignore the true winner and award the block to a colluding partner.
**- Affected Files:** `include/class/Masternode.php` (specifically `verifyBlock()`)
**- Severity:** Medium (Requires collusion, but undermines the fairness of the reward system)

---

## Part 2: Application-Level Vulnerabilities

### 4. SQL Injection in Smart Contracts

**- Vulnerability:** A critical SQL injection vulnerability exists in the `query()` method of the `SmartContractBase` class.
**- How it Works:** The `$sql` parameter, which can be fully controlled by a smart contract's code, is directly concatenated into a live SQL query. A malicious smart contract can be deployed that uses this method to execute arbitrary SQL, allowing it to read, modify, or delete any data in the node's database, including account balances.
**- Affected File:** `include/class/sc/SmartContractBase.php`
**- Severity:** Critical

### 5. Cross-Site Scripting (XSS) in Block Explorer

**- Vulnerability:** The block explorer is vulnerable to stored XSS attacks.
**- How it Works:** Transaction `message` fields are rendered directly into the HTML of the transaction and address pages without proper sanitization (e.g., `htmlspecialchars`). An attacker can create a transaction with a malicious JavaScript payload in its message field. When any user views this transaction or an address page that lists it, the script will execute in their browser, potentially leading to session theft or other client-side attacks.
**- Affected Files:** `web/apps/explorer/tx.php`, `web/apps/explorer/address.php`
**- Severity:** High

### 6. Command Injection in Admin Panel

**- Vulnerability:** The admin panel has a command injection vulnerability.
**- How it Works:** The "Add Peer" functionality takes the `peer` parameter from a POST request and concatenates it directly into a `shell_exec` command without sanitization. An authenticated admin can abuse this to execute arbitrary commands on the server's operating system with the privileges of the web server user.
**- Affected File:** `web/apps/admin/index.php`
**- Severity:** Critical (assuming attacker gains admin access)
