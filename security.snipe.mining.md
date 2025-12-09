# Security Analysis: SNIPE Miner Strategy

A SNIPE miner is a malicious actor who exploits the blockchain's fork-resolution mechanism to steal a block reward from an honest miner. This is accomplished by intentionally forcing a 1-block chain reorganization.

## The Attack Explained

1.  **Block Discovery:** An honest miner finds a new block, `Block N`, at an `elapsed` time of `X` seconds and broadcasts it to the network.

2.  **SNIPE Miner Action:** A SNIPE miner, upon seeing `Block N`, ignores it and attempts to **refind** `Block N` at a lower `elapsed` time (e.g., `X-1`). This is computationally more difficult but can be achieved with sufficient hashing power.

3.  **The Race:** The SNIPE miner must **refind** `Block N` before the honest network finds `Block N+1`.

4.  **Forcing a Reorganization:** If the SNIPE miner is successful, they submit their version of `Block N` to their node. The node adds it to its local chain and then broadcasts it. When other nodes receive this new block, it triggers the chain's reorganization logic.

## Code Exploitation

A successful SNIPE attack requires a two-stage process. The miner must first circumvent a key validation check on their own node, and then their node must propagate the block to peers, triggering the network-wide reorganization.

### Part 1: Miner-to-Node Block Submission (Circumventing the Height Check)

The `web/mine.php` script contains a critical check that, at first glance, appears to prevent the SNIPE attack:

```php
// web/mine.php -> q=submitHash

$blockchainHeight = Block::getHeight();
if ($blockchainHeight != $height - 1) {
    // ...
    api_err("rejected - not top block height=$height blockchainHeight=$blockchainHeight");
}
```

This code ensures that any submitted block must be the *next* block in the chain (`height - 1`). If an honest `Block N` has already been received and processed by the SNIPE miner's node, its `blockchainHeight` will be `N`. A refound `Block N` with `height = N` will fail this check (`N != N-1`) and be rejected.

**To circumvent this, the SNIPE miner must perform a more sophisticated attack:**

1.  **Isolate the Node:** The miner must temporarily isolate their *own node* from the network to prevent it from learning about the honest `Block N`. The node's local `blockchainHeight` remains at `N-1`.
2.  **External Block Monitoring:** The miner uses a separate, non-mining connection to the network to monitor for new blocks. When the honest `Block N` is found, the miner's software begins the process of refinding it.
3.  **Submit the SNIPE Block:** Once the SNIPE miner successfully refinds `Block N`, they submit it to their *isolated node*. Since the node's height is still `N-1`, the block passes the height check (`N == (N-1) + 1`) and is added to the node's local chain.
4.  **Reconnect and Propagate:** The miner then reconnects their node to the network. The node, now believing its SNIPE block is the canonical `Block N`, propagates it to its peers.

### Part 2: Node-to-Peer Reorganization

When a peer receives the SNIPE block from the now-reconnected node, it triggers the fork-resolution logic in `include/class/PeerRequest.php`.

```php
// include/class/PeerRequest.php -> submitBlock() method

if ($current['height'] == $data['height'] && $current['id'] != $data['id']) {
    // ...
    // This is the core vulnerability. It prioritizes the block with the lower elapsed time.
    $accept_new = $data['elapsed'] < $ourblock['elapsed'];

    if ($accept_new) {
        // Executes a microsync to replace the honest block with the SNIPE block.
        system(  "php $dir/microsync.php '$ip'  > /dev/null 2>&1  &");
        // ...
    }
}
```

Since the SNIPE block has the same `height` as the honest block but a lower `elapsed` time, the peer's node will favor it. The `microsync.php` script is then executed, which removes the honest miner's block (`Block::pop(1)`) and replaces it with the SNIPE miner's block, completing the attack.

## Effectiveness of SNIPE Mining

The viability of a SNIPE attack is a probabilistic race between the SNIPE miner and the honest network. The success of the attack depends on the SNIPE miner's hashrate (`α`, as a fraction of the total network hashrate) and the `elapsed` time (`X`) of the block they are attempting to refind.

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

**Conclusion:** The attack is most viable when an honest block is found after a long delay, as this makes the refinding task only marginally harder for the SNIPE miner. The attack is nearly impossible when a block is found quickly. This creates a high-risk, low-reward scenario for the attacker, as they are expending significant computational resources on a task that is only sometimes profitable.

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
