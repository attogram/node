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

A robust defense against the Timewarp attack requires a multi-layered approach to ensure network-wide time synchronization.

1.  **Implement Median Time Past (MTP):** This is the primary defense. A new block should only be accepted if its timestamp is greater than the median timestamp of the last 11 blocks. This prevents any single miner from manipulating the timestamp significantly.
2.  **Use a Network Time Protocol (NTP) Client:** Each node should periodically synchronize its system clock with a trusted NTP server. This prevents the node's local clock from drifting significantly, which is a prerequisite for some time-based attacks.
3.  **Implement Peer-to-Peer Time Checks:** Nodes should query each other for the time and refuse to connect to peers whose clocks are too far out of sync with their own. This helps to isolate nodes with incorrect time and maintain a consistent network time.

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

A layered defense is the most effective way to prevent Future-Push and other "slip time" attacks.

1.  **Drastically Reduce the Future-Dating Window:** This is the most direct defense. Lower the maximum acceptable future timestamp from the current `time() + 30` to a much smaller value, such as `time() + 2`. This provides a small buffer for network latency and clock drift without being large enough to be gameable.
2.  **Implement Median Time Past (MTP):** As with the Timewarp attack, an MTP check will ensure that a block's timestamp is consistent with the recent history of the blockchain, preventing large deviations into the future.
3.  **Synchronize Clocks with NTP:** Nodes should use an NTP client to keep their local system time accurate, reducing the likelihood of network splits caused by clock drift.

---

## 3. Analysis of Required Hash Power

This section provides a theoretical analysis of the hash power required to successfully execute these attacks, based on the following approximate network statistics:
-   **Average Block Time:** ~65 seconds
-   **Average Hash Rate:** ~1600 H/s

### Timewarp Attack

The Timewarp attack is a **post-mining timing manipulation**. It does not provide any advantage in *finding* a valid hash. A miner must first find a genuinely valid block through normal mining, competing with the rest of the network's hash power.

Therefore, this attack **does not reduce the hash power required** to solve a block. Its primary purpose is to manipulate the `elapsed` time to influence the difficulty calculation for *subsequent* blocks.

### Future-Push Attack

The Future-Push attack provides a significant and direct advantage to the miner by allowing them to find a valid block in much less time than normally required.

-   **Normal Mining:** A miner must search for a hash until the `elapsed` time is high enough to lower the `target` to a point where their `hit` is valid. Based on network statistics, this takes an average of **65 seconds**.

-   **Exploit Mining:** A miner using the Future-Push attack can find a block with a `hit` that is initially invalid. By setting the timestamp forward by 29 seconds, they are effectively mining against a `target` that is appropriate for an `elapsed` time of `current_elapsed + 29`. This allows them to find a "valid" block much earlier.

A simplified calculation shows that a miner can solve a block in as little as **10 seconds**, then push the timestamp forward to make it valid. This represents an **~85% reduction** in the time required to find a block, which translates to a massive amplification of the miner's effective hash power. A miner with only a fraction of the network's hash rate could successfully use this attack to find blocks far more frequently than their hash power would normally allow.
