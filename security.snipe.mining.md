# Security Analysis: SNIPE Miner Strategy

A SNIPE miner is a malicious actor who exploits the blockchain's fork-resolution mechanism to steal a block reward from an honest miner. This is accomplished by intentionally forcing a 1-block chain reorganization.

## The Attack Explained

1.  **Block Discovery:** An honest miner finds a new block, `Block N`, at an `elapsed` time of `X` seconds and broadcasts it to the network.

2.  **SNIPE Miner Action:** A SNIPE miner, upon seeing `Block N`, ignores it and attempts to **refind** `Block N` at a lower `elapsed` time (e.g., `X-1`). This is computationally more difficult but can be achieved with sufficient hashing power.

3.  **The Race:** The SNIPE miner must **refind** `Block N` before the honest network finds `Block N+1`.

4.  **Forcing a Reorganization:** If the SNIPE miner is successful, they broadcast their version of `Block N`. When a node receives this new block, it triggers the chain's reorganization logic.

## Code Exploitation

The core of the SNIPE attack is the fork-resolution logic found in `include/class/PeerRequest.php`. When a node receives a block that has the same height as its current block, but a different ID, it executes the following code:

```php
// include/class/PeerRequest.php -> submitBlock() method

if ($current['height'] == $data['height'] && $current['id'] != $data['id']) {
    $accept_new = false;
    _log("submitBlock:: DIFFERENT FORKS SAME HEIGHT", 3);

    // ... block comparison logic ...

    if($data['elapsed']==$ourblock['elapsed']) {
        if($data['date']==$ourblock['date']) {
            $accept_new = strcmp($data['id'], $ourblock['id']);
        } else {
            $accept_new = $data['date'] < $ourblock['date'];
        }
    } else {
        $accept_new = $data['elapsed'] < $ourblock['elapsed'];
    }

    if ($accept_new) {
        // if the new block is accepted, run a microsync to sync it
        _log('submitBlock: ['.$ip."] Starting microsync - $data[height]",1);
        $ip=escapeshellarg($ip);
        $dir = ROOT."/cli";
        system(  "php $dir/microsync.php '$ip'  > /dev/null 2>&1  &");
        api_echo("microsync");
    }
}
```

This code explicitly gives preference to the block with the lower `elapsed` time. If the SNIPE miner's block has a smaller `elapsed` time, the `$accept_new` flag is set to `true`, and the `microsync.php` script is executed.

The `microsync.php` script then carries out the reorganization:

```php
// cli/microsync.php

// ...
// delete the last block
Block::pop(1);

// add the new block
// ...
$res = $block->add($err);
// ...
```

This script removes the honest miner's block from the chain and replaces it with the SNIPE miner's block, effectively stealing the block reward.

## Effectiveness of SNIPE Mining

The viability of a SNIPE attack is not guaranteed. It is a probabilistic race between the SNIPE miner and the honest network. The effectiveness depends primarily on the SNIPE miner's share of the total network hashrate.

### Hashrate Requirements

A SNIPE attack is a race to solve a cryptographic puzzle. Let's analyze the two competing tasks:
1.  **The SNIPE Miner's Task:** Refind `Block N` at `elapsed = X-1`.
2.  **The Honest Network's Task:** Find the next block, `Block N+1`, which is expected to be found at `elapsed` of approximately 60 seconds.

According to the logic in `Block.php`, a block with a lower `elapsed` time is computationally **harder** to find. The difficulty is inversely proportional to the `elapsed` time. Therefore, the task of refinding the block at `elapsed = X-1` is more difficult than finding it at `elapsed = X`.

Let's quantify this. The amount of computational work required to find a block is proportional to its difficulty. If an honest miner finds `Block N` at a typical `elapsed` time of 60 seconds, the SNIPE miner attempting to refind it at `elapsed = 59` is undertaking a task that is `60/59` (approximately 1.7%) harder.

For the attack to be successful, the SNIPE miner must complete this harder task before the rest of the network can complete the easier task of finding the next block. This requires a significant amount of hashing power.

A simplified model of this race shows that the SNIPE miner's required hashrate (`α`, as a fraction of the total network hashrate) must be:

`α > (X) / (2X - 1)`

Where `X` is the `elapsed` time of the honestly mined block. For a typical block found at `X=60`, the required hashrate is:

`α > 60 / (120 - 1) ≈ 50.4%`

This means that to have a greater than 50% chance of success, **a SNIPE miner needs to control more than half of the total network hashrate.**

### When is the Attack Ineffective?

The attack is ineffective under the following conditions:
-   **Low Hashrate:** As shown above, a miner with a small fraction of the network hashrate has a negligible chance of successfully refinding the block before the honest network finds the next one.
-   **Low `elapsed` Time:** If an honest miner finds a block very quickly (e.g., at `elapsed = 10`), the SNIPE miner's task of refinding it at `elapsed = 9` is significantly harder (`10/9` or ~11% harder). This increases the hashrate required to pull off the attack, making it even less likely to succeed.

### The Impact of Network Latency

Network latency can slightly lower the required hashrate threshold. If a SNIPE miner is well-connected and learns of a new block several seconds before the majority of the honest network, they get a head start on the race. However, even with a significant latency advantage, the required hashrate remains substantial, making the attack impractical for all but the largest mining pools.

## Possible Defenses

The SNIPE attack is only possible because the fork-resolution logic in `PeerRequest.php` uses `elapsed` time as a tie-breaker. By removing this incentive, the attack can be neutralized without requiring any changes to the core mining algorithm or block structure.

### Modified Fork-Choice Rule

The most effective and least disruptive defense is to modify the fork-choice rule to **ignore the `elapsed` time** when two blocks of the same height are being compared. Instead, the tie-breaker should be a deterministic but unpredictable value, such as the block's ID.

The code in `PeerRequest.php` should be changed to the following:

```php
// include/class/PeerRequest.php -> submitBlock() method (RECOMMENDED CHANGE)

if ($current['height'] == $data['height'] && $current['id'] != $data['id']) {
    $accept_new = false;
    _log("submitBlock:: DIFFERENT FORKS SAME HEIGHT", 3);

    // The block with the lexicographically smaller ID wins.
    $accept_new = strcmp($data['id'], $ourblock['id']) < 0;

    if ($accept_new) {
        // if the new block is accepted, run a microsync to sync it
        _log('submitBlock: ['.$ip."] Starting microsync - $data[height]",1);
        $ip=escapeshellarg($ip);
        $dir = ROOT."/cli";
        system(  "php $dir/microsync.php '$ip'  > /dev/null 2>&1  &");
        api_echo("microsync");
    }
}
```

By making the block ID the sole tie-breaker, there is no longer any incentive for a miner to refind a block at a lower `elapsed` time. The winner of a tie is essentially random, and the SNIPE attack is no longer profitable. This change can be implemented via a soft fork, as it only tightens the rules for block acceptance.
