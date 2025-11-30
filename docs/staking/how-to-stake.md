[PHPCoin Docs](../) > [Staking](./) > How to Stake

---

# How to Stake

## Introduction

Staking is the process of holding PHPCoin in your wallet to support the blockchain network. In return for holding coins, you receive rewards in the form of new PHPCoin.

## How Staking Works

PHPcoin uses a Proof-of-Stake (PoS) system to reward coin holders. Unlike miners or block generators, **stakers do not create or validate blocks**. Instead, for each block created, the network automatically chooses a "stake winner" from all eligible participants to receive a reward.

To be eligible for staking rewards, you must meet two main requirements: a minimum balance and a coin maturity period.

## How to Start Staking

To become eligible for staking rewards, you must first send a special "stake" transaction. This is a one-time action that flags your address as a staking participant.

1.  **Open a terminal or command prompt.**
2.  **Navigate to the directory where your PHPcoin wallet is located.**
3.  **Run the following command:**

    ```bash
    php cli/wallet.php send <your-address> 0 "stake"
    ```

    Replace `<your-address>` with your own wallet address. The `0` is the amount to send (this special transaction is free), and `"stake"` is the message that activates your address for staking.

## Technical Details

### 1. Activating an Address for Staking

An address is recognized as a staking address after it has been the destination of a transaction with the message `"stake"`.

*   **Code Reference:** The `getAddressTypes` function in `include/class/Block.php`.

### 2. Staking Requirements

#### Coin Maturity

Your coins must be held for a certain number of blocks before they are considered "mature" for staking.

*   **Before block 290,000:** 600 blocks.
*   **At or after block 290,000:** **60 blocks**.

*   **Code Reference:** This logic is implemented in the `getStakingMaturity()` function in `include/class/Blockchain.php`. The block height for this change is controlled by the `UPDATE_11_STAKING_MATURITY_REDUCE` constant, which is defined with the value `290000` in `include/coinspec.inc.php`.

#### Minimum Balance

You must hold a minimum number of coins to be eligible for staking. This amount changes at specific block heights.

##### Current Requirement (Block 1,000,001+)

**For the current mainnet (at over 1,250,000 blocks), the minimum staking balance is 160,000 PHPCoin.** This value is fixed for all blocks from 1,000,001 onward.

##### Historical Requirements

*   **Before block 290,000:** 100 PHPCoin.
*   **Block 290,001 - 1,000,000:** The minimum balance increased in stages, starting from 30,000 PHPCoin.

*   **Code Reference:** The `getStakingMinBalance()` function in `include/class/Blockchain.php` determines the minimum balance. The switch to a dynamic balance occurs at block 290,000, which is defined by the `UPDATE_12_STAKING_DYNAMIC_THRESHOLD` constant (value `290000`) in `include/coinspec.inc.php`. After this block, the function calculates the minimum as twice the masternode collateral value, which is defined in the `REWARD_SCHEME` constant in `include/rewards.inc.php`.

### 3. Stake Winner Selection

For each block, a single stake winner is selected from all eligible accounts based on a `weight`.

*   **Code Reference:** The `getStakeWinner` function in `include/class/Account.php` calculates this `weight` using the formula: `(current_block_height - last_transaction_height) * account_balance`. The account with the highest weight wins.

### 4. Staking Rewards

The reward amount is determined by the block height.

*   **Code Reference:** The `reward` function in `include/class/Block.php` reads the reward structure from the `REWARD_SCHEME` constant in `include/rewards.inc.php`.
