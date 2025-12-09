# Security Analysis: SNIPE Miner Strategy

A SNIPE miner is a malicious actor who exploits the blockchain's fork-resolution mechanism to steal a block reward from an honest miner. This is accomplished by intentionally forcing a 1-block chain reorganization.

## The Attack Explained

1.  **Block Discovery:** An honest miner finds a new block, `Block N`, at an `elapsed` time of `X` seconds and broadcasts it to the network.

2.  **SNIPE Miner Action:** A SNIPE miner, upon seeing `Block N`, ignores it and continues to mine for their own version of `Block N` at a lower `elapsed` time (e.g., `X-1`). This is computationally more difficult but can be achieved with sufficient hashing power.

3.  **The Race:** The SNIPE miner must find their block before the honest network finds `Block N+1`.

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

## Possible Defenses (Non-Disruptive)

Given the constraint that the mining algorithm cannot be changed, the following defenses could be implemented as a soft fork:

### 1. Increase Confirmation Time

The most straightforward defense is to increase the number of confirmations required before a block is considered final. While this does not prevent the SNIPE attack itself, it mitigates its impact. If, for example, a transaction is not considered final until it has 6 confirmations, a 1-block reorganization would be less likely to cause significant damage.

### 2. "First Seen" Rule

The fork-resolution logic in `PeerRequest.php` could be modified to prioritize the block that was *seen first*. This would involve nodes keeping a record of when they first received a block and using that timestamp as the primary tie-breaker in the event of a fork. This would neutralize the SNIPE attack, as the honestly-mined block would almost always be seen first.

### 3. Penalize Block Withholding

A more complex defense would be to introduce a penalty for broadcasting a block with a significantly lower `elapsed` time than the current block. This would disincentivize SNIPE miners by making their attack less profitable. This could be implemented by adding a check in the `submitBlock` method that rejects blocks with an `elapsed` time that is too far below the expected value.
