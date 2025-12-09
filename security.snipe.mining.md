# Security Analysis: SNIPE Miner Strategy

This document analyzes the viability of a "SNIPE" mining attack. The analysis concludes that the attack is **impossible** for external miners but **possible** for a malicious node operator who can modify their node's internal mining software.

## The External Miner: Attack Impossible

An external miner submits blocks to a node via the `web/mine.php` API. This code path contains a fundamental protection that makes the SNIPE attack impossible.

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

**Conclusion:** The external mining API is secure against this attack vector.

## The Malicious Node Operator: Attack Possible via `Block::pop`

A malicious node operator can successfully perform a SNIPE attack by modifying their `NodeMiner.php` script.

### The Exploit Path

The standard `NodeMiner.php` script stops mining when it detects a new block. A malicious operator would modify this logic to *continue* attempting to refind the block at a lower `elapsed` time.

Upon successfully refinding the block, the malicious script executes a two-step process:

1.  **`Block::pop(1);`**
    The script first calls the `Block::pop(1)` function. This function deletes the most recent block from the node's local blockchain. In this case, it removes the honest `Block N` that the node had previously accepted. This action instantly reverts the node's local height back to `N-1`.

    ```php
    // include/class/Block.php -> pop() method
    public static function pop($no = 1)
    {
        $current = Block::current();
        return Block::delete($current['height'] - $no + 1);
    }
    ```

2.  **`$block->add();`**
    Immediately after popping the honest block, the script calls `$block->add()` to submit its own refound `Block N`. The `add()` method's height check now passes (`($N - (N-1)) == 1`), and the SNIPE block is successfully added to the node's local chain.

3.  **`Propagate::blockToAll("current");`**
    After successfully adding the block, the malicious `NodeMiner` script calls `Propagate::blockToAll("current")`. This broadcasts the SNIPE block to all of the node's peers. The peers, seeing a block with the same height as the one they have but with a lower `elapsed` time, are forced into a 1-block reorganization, thus completing the attack.

**Conclusion:** The SNIPE attack is a viable threat, but it can only be executed by a malicious node operator with the ability to modify their own node's source code.

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
    *   To have a 50% chance of success, a SNIPE miner needs `α ≈ 87%` of the network hashrate. The extreme difficulty of refinding a block with such a low `elapsed` time makes the attack extremely difficult without an overwhelming hashrate advantage.

*   **Honest block found at `X=120` (very slowly):**
    *   To have a 50% chance of success, a SNIPE miner only needs `α ≈ 33.5%` of the network hashrate.

**Conclusion:** The attack is most viable when an honest block is found after a long delay, as this makes the refinding task only marginally harder for the SNIPE miner. Conversely, the attack becomes significantly more difficult when a block is found quickly, as this increases the relative difficulty of the refinding task.

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
