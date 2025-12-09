# Security Analysis: SNIPE Miner Strategy

A SNIPE miner is a malicious actor who attempts to steal a block reward from an honest miner by intentionally causing a 1-block chain reorganization. This strategy exploits the core mining mechanics of the PHPCoin blockchain, specifically how the `elapsed` time between blocks influences mining difficulty.

## The Attack Explained

The SNIPE attack unfolds as a race between the SNIPE miner and the rest of the honest network. Here is the step-by-step process:

1.  **Block Discovery:** An honest miner successfully finds a new block, let's call it `Block N`, at a certain `elapsed` time (the number of seconds since the previous block). Let's say this time is `X` seconds. The honest miner broadcasts this block to the network.

2.  **Honest Network Behavior:** Upon receiving `Block N`, the other honest miners on the network verify it and immediately begin mining the next block, `Block N+1`, using `Block N` as the new starting point.

3.  **SNIPE Miner Behavior:** The SNIPE miner, however, does something different. When it sees the newly discovered `Block N`, it *continues* to mine for its *own* version of `Block N`. It is essentially trying to "re-find" the same block but at a lower `elapsed` time.

4.  **The Race:** The SNIPE miner is now in a direct race. It must find its own valid version of `Block N` *before* the entire honest network can find `Block N+1`.

5.  **Winning the Race:** If the SNIPE miner is successful, it immediately broadcasts its version of `Block N`. Nodes on the network will now have two competing, valid blocks at the same height.

6.  **Forcing a Reorganization:** According to the chain's consensus rules, when two valid blocks at the same height are presented, the block with the **lowest `elapsed` time** is chosen as the winner. The SNIPE miner's goal is to find a block with an `elapsed` time of `X-1` or lower. If it does, its block will be accepted, forcing a 1-block reorganization and stealing the reward from the original, honest miner.

## Code Exploitation

The viability of the SNIPE attack hinges on the `calculateTarget` function within the `include/class/Block.php` file.

```php
// include/class/Block.php

function calculateTarget($elapsed) {
    global $_config;
    if($elapsed == 0) {
        return 0;
    }
    $target = gmp_div(gmp_mul($this->difficulty , BLOCK_TIME), $elapsed);
    // ...
    return $target;
}
```

This function directly ties the mining `target` to the `$elapsed` time. The `target` is what a miner's `hit` must exceed to mine a valid block. The formula shows that the `target` is inversely proportional to the `elapsed` time.

-   A **larger** `$elapsed` time (more seconds since the last block) results in a **lower** `target`, making the block easier to mine.
-   A **smaller** `$elapsed` time results in a **higher** `target`, making the block significantly harder to mine.

A SNIPE miner exploits this by continuing to work on Block `N`. If an honest miner finds Block `N` at `elapsed=60`, the SNIPE miner might try to find it at `elapsed=59`. While this is computationally much harder (a higher target), if the SNIPE miner has enough hashing power, it can potentially succeed before the honest network finds Block `N+1` (which would take approximately another 60 seconds).

The mining loop itself, found in `include/class/NodeMiner.php`, facilitates this process. The miner continuously checks for new blocks from the network, but a malicious miner can simply modify this logic to ignore new blocks for a short period while they attempt the SNIPE.

```php
// include/class/NodeMiner.php -> start() method

// ... inside the while (!$blockFound) loop
if($this->attempt % $mod == 0) {
    $info = $this->getMiningInfo();
    if($info!==false) {
        _log("Checking new block from server ".$info['block']. " with our block $prev_block_id", 4);
        if($info['block']!= $prev_block_id) {
            _log("New block received", 3);
            $this->miningStat['dropped']++;
            break; // Honest miner stops and starts on the new block
        }
    }
}
```

A SNIPE miner would alter or disable this check, allowing it to continue mining its own version of the block, creating the race condition that enables the attack.

## Possible Defenses

Mitigating the SNIPE attack requires changes to the consensus rules to disincentivize the withholding of blocks. Here are a few potential defense strategies:

### 1. Timestamp-Based Fork Resolution

Instead of relying on the `elapsed` time, the fork resolution logic could be modified to favor the block that was seen *first*. This would involve nodes keeping a record of when they first received a block and using that timestamp as the primary tie-breaker. This would neutralize the SNIPE attack, as the honestly-mined block would almost always be seen first.

### 2. "Publish or Perish" Strategy

This strategy, proposed for Bitcoin, would require a block to be published within a certain time frame after it's mined. If a miner withholds a block for too long, it would be considered invalid. This would make it much more difficult for a SNIPE miner to withhold their block while waiting for the perfect moment to release it.

### 3. Dynamic Difficulty Adjustment

A more complex defense would be to dynamically adjust the mining difficulty when a fork is detected. If two blocks are found at the same height, the difficulty for the next block could be temporarily increased. This would make it more computationally expensive to continue the attack, thus discouraging SNIPE miners.

Implementing any of these defenses would require a hard fork of the blockchain, as they represent a fundamental change to the consensus rules.
