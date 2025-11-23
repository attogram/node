# PHPCoin Security Analysis: Block Rewards

## Is it possible for a node to generate different rewards?

No, it is not possible for a node to generate a block with a different (e.g., higher) reward for itself. The block reward amount is determined by a hardcoded reward scheme based on the block's height.

### How Rewards are Calculated

The reward for a given block is calculated by the `Block::reward()` function in `include/class/Block.php`. This function reads a constant array called `REWARD_SCHEME` from the `include/rewards.inc.php` file. The `REWARD_SCHEME` defines the reward amounts for different block height ranges.

Here is an example from `include/rewards.inc.php`:

```php
const REWARD_SCHEME = [
    // ...
    ["increasing", "1", 20001, 200000, 3, 1, 2, 4, 0, 10000],
    // ...
];
```

For a block with a height between 20,001 and 200,000, the miner reward will always be 3 PHPCoin.

### How Rewards are Verified

When a new block is received by a node, it is verified by the `verifyBlock()` function in `include/class/Block.php`. During this process, the node independently recalculates the correct reward for the block's height using the same `REWARD_SCHEME`. It then compares this calculated reward with the reward transaction included in the block.

If a malicious node were to create a block with a reward transaction containing a higher amount, the other nodes on the network would:

1.  Independently calculate the correct reward based on the block height.
2.  See that the reward transaction in the block does not match the correct reward.
3.  Reject the block as invalid.

This consensus mechanism ensures that all nodes enforce the same rules, preventing any single node from manipulating the block reward.
