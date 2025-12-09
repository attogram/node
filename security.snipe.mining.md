# Security Analysis: SNIPE Miner Strategy

This document analyzes the viability of a "SNIPE" mining attack, where a malicious actor attempts to steal a block reward by forcing a 1-block chain reorganization. The analysis concludes that the viability of this attack depends entirely on the type of attacker.

## Threat Model: Two Attacker Types

The ability to execute a SNIPE attack is not universal. It depends on whether the attacker is an **External Miner** using the node's public API or a **Malicious Node Operator** with control over the node's internal mining logic.

### 1. The External Miner: Attack Impossible

An external miner submits their blocks to a node via the `web/mine.php` API. This code path contains a fundamental protection that makes the SNIPE attack impossible.

When a block is submitted, it is eventually passed to the `Block->add()` method, which contains this critical check:

```php
// include/class/Block.php -> add() method

$currentHeight = Block::getHeight();
if($this->height - $currentHeight != 1) {
    throw new Exception("Block height failed");
}
```
This code asserts that any new block must have a height exactly one greater than the node's current blockchain height.

**Why this prevents the attack:**
1.  When an honest `Block N` is found, the node the external miner is connected to will receive and process it, setting its internal height to `N`.
2.  If the external SNIPE miner then successfully refinds `Block N`, their refound block *also* has `height = N`.
3.  When they submit this block to the node, the check `($N - $N) != 1` fails, and the block is rejected.

For an external miner, there is no way to circumvent this check. The node they are communicating with will always be aware of the honest chain's height, making it impossible to submit a block for a height that has already been processed.

### 2. The Malicious Node Operator: Attack Possible

A malicious node operator who runs their own node and uses the built-in `NodeMiner.php` logic is the only actor who can successfully perform a SNIPE attack.

This is because they can modify their node's mining logic to create the necessary conditions for the attack. The key exploited code is in `NodeMiner.php`:

```php
// include/class/NodeMiner.php -> start() method

// ... inside the while (!$blockFound) loop
if($this->attempt % $mod == 0) {
    $info = $this->getMiningInfo();
    if($info!==false) {
        if($info['block']!= $prev_block_id) {
            _log("New block received", 3);
            break; // Honest miner stops and starts on the new block
        }
    }
}
```

**How the attack is executed:**
1.  **Modify the Miner:** The malicious node operator modifies the code above, commenting out or disabling the `break` statement. This prevents their internal miner from stopping when it learns of a new honest block.
2.  **The Race:** When an honest `Block N` is found, the malicious node's `NodeMiner` ignores it and continues its attempt to refind `Block N` at a lower `elapsed` time.
3.  **Local Block Acceptance:** Because the node's *miner* is ignoring the new block, the node's *own blockchain height* remains at `N-1`. When the malicious miner successfully refinds `Block N`, their own `Block->add()` method is called. The height check `($N - (N-1)) != 1` passes, and the SNIPE block is accepted into the local chain.
4.  **Propagation and Reorganization:** The malicious node, now believing its SNIPE block is the valid `Block N`, propagates it to its peers. The peers, seeing a block with the same height but a lower `elapsed` time, are forced into a 1-block reorganization as described in the `PeerRequest.php` logic.

**Conclusion:** The SNIPE attack is not a vulnerability in the external mining API, but rather an exploit possible only by a malicious node operator who can alter their own mining software.

## Effectiveness of SNIPE Mining (by a Malicious Node Operator)

The viability of a SNIPE attack is a probabilistic race. Its success depends on the malicious node operator's hashrate (`α`, as a fraction of the total network hashrate) and the `elapsed` time (`X`) of the block they are attempting to refind.

### The Race Condition

The race is between two competing Poisson processes:
1.  **The SNIPE Miner:** Trying to refind `Block N` at `elapsed = X-1`. The effective hashrate for this task is `α * (X-1) / 60`, as their task is `X / (X-1)` times harder than a standard 60-second block.
2.  **The Honest Network:** Trying to find `Block N+1`. The effective hashrate for this task is `(1-α)`.

The probability of the SNIPE miner winning this race is the ratio of their effective hashrate to the total effective hashrate:

`P(SNIPE wins) = (α * (X-1) / 60) / (α * (X-1) / 60 + (1-α))`

This formula shows that the SNIPE miner's chances increase with their hashrate (`α`) and as the `elapsed` time (`X`) of the honest block gets larger.

### Example Scenarios

Let's analyze the hashrate (`α`) required to achieve a 50% chance of success for different values of `X`:

*   **Honest block found at `X=60` (average):**
    *   To have a 50% chance of success, a SNIPE miner needs `α ≈ 50.4%` of the network hashrate.

*   **Honest block found at `X=10` (very quickly):**
    *   To have a 50% chance of success, a SNIPE miner needs `α ≈ 87%` of the network hashrate. The extreme difficulty of refinding a block with such a low `elapsed` time makes the attack nearly impossible without an overwhelming hashrate advantage.

*   **Honest block found at `X=120` (very slowly):**
    *   To have a 50% chance of success, a SNIPE miner only needs `α ≈ 33.5%` of the network hashrate.

**Conclusion:** The attack is most viable when an honest block is found after a long delay, as this makes the refinding task only marginally harder for the SNIPE miner. The attack is nearly impossible when a block is found quickly.

## Possible Defenses

Defenses against the SNIPE attack can be categorized by the level of network consensus required to implement them.

### No Fork (Application-Level Mitigation)

These are not protocol-level defenses and do not prevent the attack itself. Instead, they mitigate its impact on services built on top of the blockchain.

*   **Increased Confirmation Time:** Applications, such as exchanges or merchants, can increase the number of block confirmations they require before considering a transaction final. A 1-block reorganization from a SNIPE attack would not affect a transaction that requires 6 or more confirmations. This is a human/policy-level decision, not a change to the chain's code.

### Soft Fork Defenses

A soft fork is a backward-compatible change to the consensus rules. In the context of the SNIPE attack, a soft fork solution is difficult to implement. A soft fork can only make the rules for block validity *stricter*. One could, in theory, create a rule that invalidates blocks with an `elapsed` time that is "too low," but defining "too low" in a way that doesn't risk invalidating honest blocks due to network latency is extremely challenging.

### Hard Fork Defenses

A hard fork is a non-backward-compatible change that requires all nodes on the network to upgrade. This is the most direct way to neutralize the SNIPE attack.

*   **Modified Fork-Choice Rule:** The most effective defense is to remove the incentive for the attack by changing the fork-choice rule. The `elapsed` time should be removed as a factor in deciding the winning block in a 1-block fork. Instead, a deterministic but unpredictable value, such as the block's ID, should be used as the tie-breaker.

    The code in `PeerRequest.php` could be changed as follows:

    ```php
    // include/class/PeerRequest.php -> submitBlock() method (RECOMMENDED CHANGE)

    if ($current['height'] == $data['height'] && $current['id'] != $data['id']) {
        $accept_new = false;
        _log("submitBlock:: DIFFERENT FORKS SAME HEIGHT", 3);

        // The block with the lexicographically smaller ID wins.
        $accept_new = strcmp($data['id'], $ourblock['id']) < 0;

        if ($accept_new) {
            // ... (microsync logic)
        }
    }
    ```

    By making the block ID the sole tie-breaker, there is no longer any benefit to refinding a block at a lower `elapsed` time. This eliminates the profitability of the SNIPE attack. This change is a **hard fork** because it fundamentally alters how nodes determine the canonical chain. Nodes running the old software would still follow the `elapsed` time rule, leading to a permanent chain split if a SNIPE attack were to occur.
