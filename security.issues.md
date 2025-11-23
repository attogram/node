# PHPCoin Security Issues Summary

This document summarizes the major security vulnerabilities discovered during a security audit.

## 1. Reward Redirection (Miner & Staker)

**- Vulnerability:** A malicious block generator can steal the rewards intended for miners and stakers.
**- How it Works:** The block validation logic correctly checks the *amount* of a miner or staker reward transaction, but it fails to verify the *destination*. A generator can create a block that correctly identifies the miner/staker in the header, but then create a reward transaction that sends the funds to an address they control. Other nodes will accept this block as valid.
**- Affected Files:** `web/mine.php`, `include/class/Transaction.php` (specifically `checkRewards()`)
**- Severity:** High

## 2. Illegitimate Stake Reward Forgery

**- Vulnerability:** A malicious block generator who is also an *eligible staker* can forge a stake reward transaction for themselves, even if they were not the legitimate, consensus-determined winner for that block.
**- How it Works:** When validating a stake reward, the network checks that the recipient is an *eligible* staker (i.e., meets the balance and maturity requirements), but it **does not** re-run the deterministic winner selection algorithm (`Account::getStakeWinner()`) to confirm the recipient was the *correct* winner for that specific block. This allows any eligible staker who is also generating a block to illegitimately claim the stake reward.
**- Affected Files:** `include/class/Transaction.php` (specifically `checkRewards()`)
**- Severity:** High

## 3. Masternode Winner Selection Manipulation (Collusion Attack)

**- Vulnerability:** A malicious block generator can collude with any other verified masternode to bypass the deterministic winner selection process and assign them the block reward, even when they are not the legitimate winner.
**- How it Works:** Similar to the staking vulnerability, the block validation logic (`Masternode::verifyBlock()`) does not re-run the deterministic winner selection algorithm (`Masternode::getWinner()`). It only checks that the masternode specified in the block header is a valid, verified masternode and that its signature on the block is correct. This allows a generator to ignore the true winner and award the block to a colluding partner.
**- Affected Files:** `include/class/Masternode.php` (specifically `verifyBlock()`)
**- Severity:** Medium (Requires collusion, but undermines the fairness of the reward system)
