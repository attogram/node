# PHPCoin Security Analysis: Staking Rewards

## Can a bad generator add a stake reward where none should exist?

Yes, but with an important caveat: a malicious block generator who is also an *eligible staker* can forge a stake reward transaction for themselves, even if they were not the legitimate, consensus-determined winner for that block.

This vulnerability exists because while the block creation process correctly identifies a single stake winner, the block validation process fails to re-verify that winner.

### The Attack

1.  **Winner Selection (During Block Creation):** When a node generates a new block, it calls `Transaction::getStakeRewardTx()`, which in turn calls `Account::getStakeWinner()`. This function deterministically selects a single winner from all eligible stakers on the network based on their staking weight. This part of the process works correctly.

2.  **The Flaw (During Block Validation):** When a node receives a new block from a peer, it validates all transactions within it. For a stake reward transaction (`msg == "stake"`), it calls the `Transaction::checkRewards()` function. However, this validation function **does not** re-run the `Account::getStakeWinner()` selection algorithm to check if the recipient of the reward was the actual winner.

    Instead, it only performs a general check to confirm the recipient is an eligible staker. To be eligible, an account must meet two criteria, as defined in `include/class/Blockchain.php`:
    *   **Maturity:** The account must not have had any transactions for a specific number of blocks. This is **600 blocks** initially, reduced to **60 blocks** after `UPDATE_11_STAKING_MATURITY_REDUCE`.
    *   **Balance:** The account must hold a minimum balance. This is **100 PHPCoin** initially, but becomes dynamic (2x the masternode collateral) after `UPDATE_12_STAKING_DYNAMIC_THRESHOLD`.

3.  **Exploitation:** A malicious block generator can exploit this flaw.
    *   The generator must also be an eligible staker (i.e., they meet the balance and maturity requirements described above).
    *   When creating a new block, the generator can simply ignore the real winner from `Account::getStakeWinner()` and instead insert a stake reward transaction with themselves as the destination.
    *   When they broadcast this block, other nodes will validate it. The `checkRewards` function will see a stake reward sent to an address that meets the eligibility criteria, confirm the reward *amount* is correct, and approve the transaction.

    It is important to note that a generator who is **not** an eligible staker cannot perform this attack. If they were to create a stake reward transaction for themselves, it would fail the validation checks for staking maturity and balance, causing the entire block to be rejected. The vulnerability is limited to accounts that are already valid stakers.

The consensus protocol is missing the crucial step of verifying that `stake_reward_transaction.dst == Account::getStakeWinner(block.height)`. This allows any eligible staker who is also generating a block to illegitimately claim the stake reward for that block.
