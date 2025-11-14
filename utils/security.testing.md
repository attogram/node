# Consensus Exploit Testing Guide

**Important Note:** The tools and descriptions in this document are for testing and validation purposes only. They demonstrate potential exploits that have been identified in security audits. The functionality of these miners has not yet been validated against a live network and should be used exclusively in controlled test environments.

---

## 1. Timewarp Attack Miner (`utils/miner.timewarp.php`)

This miner is designed to test a "Timewarp" vulnerability.

### Technical Details

The `miner.timewarp.php` script demonstrates an attack where a miner can artificially inflate the `elapsed` time between blocks to manipulate the mining difficulty. The script operates as follows:

1.  It performs a normal mining process to find a block with a valid hash (where `hit` > `target`).
2.  Once a valid block is found, the script does **not** submit it immediately.
3.  Instead, it pauses for a configurable duration (the "wait time").
4.  After waiting, it sets the block's timestamp to a future value (the "slip time").
5.  Finally, it submits the block with the manipulated timestamp.

This artificially increases the `elapsed` time since the last block, which can lower the `target` difficulty for the next block, making it easier to mine. The logic is encapsulated in a self-contained `TimewarpMiner` class within the script itself.

### How to Use

Run the miner from the command line, providing the node URL, your address, and CPU usage. You can specify the exploit parameters using the `--wait-time` and `--slip-time` options.

**Usage:**
```bash
php utils/miner.timewarp.php <node> <address> <cpu> [options]
```

**Example:**
```bash
php utils/miner.timewarp.php http://127.0.0.1 Pfoo1234567890abcdefghijklmnopqrstuvwxyz 50 --wait-time=20 --slip-time=29
```

**Options:**
-   `--wait-time=<sec>`: The number of seconds to wait after finding a block before submitting. Default: 20.
-   `--slip-time=<sec>`: The number of seconds to push the block's timestamp into the future. Default: 20 (Max: 30).

### Recommended Defense

To defend against the Timewarp attack, the codebase should be updated to implement a **Median Time Past (MTP)** check.

-   **How it works:** A new block should only be accepted if its timestamp is greater than the median timestamp of the last 11 blocks.
-   **Effectiveness:** This prevents any single miner from manipulating the timestamp by more than a few seconds, as the block's time would be rejected for being inconsistent with the median of recent blocks.

---

## 2. Future-Push Attack Miner (`utils/miner.future-push.php`)

This miner is designed to test a "Future-Push" vulnerability, which is a specific type of "slip time" attack.

### Technical Details

The `miner.future-push.php` script demonstrates an attack where a miner can validate a block that was solved too quickly (and is therefore normally invalid). The script operates as follows:

1.  It begins mining, calculating a `hit` value for each attempt.
2.  In each attempt, it checks the `hit` against two values:
    -   The **current `target`**, based on the actual `elapsed` time.
    -   A **`future_target`**, calculated with a manipulated `elapsed` time (actual elapsed + slip time).
3.  The miner finds a block when the `hit` is greater than the `future_target`, even if it is still *less than* the current `target`.
4.  Once such a block is found, it immediately submits it with a future-dated timestamp.

This allows a miner to successfully submit a block that they did not technically have the hashrate to find, by exploiting the 30-second future timestamp allowance. The logic is encapsulated in a self-contained `FuturePushMiner` class within the script.

### How to Use

Run the miner from the command line, providing the node URL, your address, and CPU usage. You can specify the exploit's future timestamp using the `--slip-time` option.

**Usage:**
```bash
php utils/miner.future-push.php <node> <address> <cpu> [options]
```

**Example:**
```bash
php utils/miner.future-push.php http://127.0.0.1 Pfoo1234567890abcdefghijklmnopqrstuvwxyz 50 --slip-time=29
```

**Options:**
-   `--slip-time=<sec>`: The number of seconds to push the block's timestamp into the future to validate an otherwise invalid block. Default: 20 (Max: 30).

### Recommended Defense

To defend against the Future-Push attack, the allowable window for future-dating blocks should be drastically reduced.

-   **How it works:** In the block validation logic, lower the maximum acceptable future timestamp from the current `time() + 30` to a much smaller value.
-   **Suggestion:** A value of `time() + 2` or `time() + 3` is sufficient to account for minor network clock drift without providing a large enough window to be exploited by this attack.
