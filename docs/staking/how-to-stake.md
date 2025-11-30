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

*   **Code Reference:** The `getAddressTypes` function in `include/class/Block.php` checks for this condition with the following SQL query:
    ```php
    $sql="select 1 from transactions t where t.dst = :address and t.type = 0 and t.message = 'stake' limit 1";
    ```

### 2. Staking Requirements

#### Coin Maturity

Your coins must be held for a certain number of blocks before they are considered "mature" for staking. The required maturity period was reduced significantly at block height 290,000.

*   **Before block 290,000:** The maturity requirement was **600 blocks**.
*   **At or after block 290,000:** The maturity requirement is **60 blocks**.

*   **Code Reference:** This logic is implemented in the `getStakingMaturity` function in `include/class/Blockchain.php`. The block height for this change is defined by the `UPDATE_11_STAKING_MATURITY_REDUCE` constant in `include/coinspec.inc.php`.

#### Minimum Balance

You must hold a minimum number of coins to be eligible for staking.

*   **Code Reference:** The `getStakingMinBalance` function in `include/class/Blockchain.php` determines this value.
    *   Before block 290,000, it returns `100`.
    *   At or after block 290,000 (defined by the `UPDATE_12_STAKING_DYNAMIC_THRESHOLD` constant in `include/coinspec.inc.php`), the minimum balance is calculated based on network parameters. The specific values are:
        *   **Block 290,001 - 300,000:** 30,000 PHPCoin
        *   **Block 300,001 - 400,000:** 40,000 PHPCoin
        *   **And so on, as defined in `include/rewards.inc.php`.**

### 3. Stake Winner Selection

For each block, a single stake winner is selected from all eligible accounts.

*   **Code Reference:** The `getStakeWinner` function in `include/class/Account.php` handles this process.
    *   It queries the `accounts` table for all addresses that meet the maturity and minimum balance requirements.
    *   It calculates a `weight` for each eligible account using the formula: `weight = (current_block_height - last_transaction_height) * account_balance`.
    *   The account with the highest weight is selected as the winner.

### 4. Staking Rewards

The staking reward amount is determined by the current block height.

*   **Code Reference:** The `reward` function in `include/class/Block.php` reads from the `REWARD_SCHEME` constant in `include/rewards.inc.php`. This constant defines the reward structure for different block height ranges. For example, for blocks 20,001 to 200,000, the `staker` reward is defined as `2`.
