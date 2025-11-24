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

### How Reward Amounts are Verified

When a new block is received by a node, it is verified by the `verifyBlock()` function in `include/class/Block.php`. During this process, the node independently recalculates the correct reward for the block's height using the same `REWARD_SCHEME`. It then compares this calculated reward with the reward transaction included in the block.

If a malicious node were to create a block with a reward transaction containing a higher amount, the other nodes on the network would:

1.  Independently calculate the correct reward based on the block height.
2.  See that the reward transaction in the block does not match the correct reward.
3.  Reject the block as invalid.

This consensus mechanism ensures that all nodes enforce the same rules, preventing any single node from manipulating the block reward amount.

## Can a malicious node redirect rewards to a different address?

Yes, for some reward types. While the *amount* of each reward is strictly enforced, the *destination* is not always verified, creating a critical vulnerability.

### Miner and Staker Rewards (Vulnerable)

A malicious block generator **can** steal the rewards intended for miners and stakers.

The `Transaction::checkRewards()` function validates that a "miner" or "stake" reward transaction has the correct value, but it **does not** verify that the destination address (`dst`) of the transaction matches the miner or staker who earned it.

An attacker can execute the following steps:
1.  Generate a new block.
2.  Correctly identify the legitimate miner in the `block->miner` field of the block header.
3.  Create a "miner" reward transaction with the correct reward amount.
4.  Set the destination address of this reward transaction to an address they control, instead of the legitimate miner's address.
5.  Submit the block.

Other nodes will accept this block as valid because all consensus rules are met: the block is correctly mined, and the reward transaction has the correct value. The consensus protocol is missing a rule to check that `reward_transaction.dst == block.miner`. The same vulnerability applies to staker rewards.

#### A Note on Proof-of-Work
It is not possible for an attacker to simply change the `miner` address in the block header to their own. The `miner` address is a critical component of the data used to calculate the block's proof-of-work hash. Changing this address would invalidate the proof-of-work, and the attacker would have to re-mine the block from scratch. The vulnerability described above is that the attacker can use a legitimate miner's proof-of-work while redirecting the funds in a separate transaction.

### Masternode and Dev Rewards (Secure)

The destinations for masternode and dev rewards are secure and cannot be redirected.

*   **Masternode Rewards:** The `Masternode::verifyBlock()` function cross-validates the reward. It confirms that the destination of the masternode reward transaction matches the collateral address of the masternode winner, as determined by the network consensus.
*   **Dev Rewards:** The `Transaction::checkRewards()` function contains a specific check to ensure that the destination of the "dev" reward transaction is the hardcoded `DEV_REWARD_ADDRESS`.
