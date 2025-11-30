[PHPCoin Docs](../) > [Staking](./) > How to Stake

---

# How to Stake

## Introduction

Staking is the process of holding PHPCoin in your wallet to support the blockchain network. In return for holding coins, you receive rewards in the form of new PHPCoin. It's a great way to earn passive income while helping to secure the network.

## How Staking Works

PHPcoin uses a Proof-of-Stake (PoS) system to reward coin holders. Unlike miners or block generators, **stakers do not create or validate blocks**. Instead, for each block created, the network automatically chooses a "stake winner" from all eligible participants to receive a reward.

To be eligible for staking rewards, you must meet two main requirements:

*   **Minimum Balance:** You must hold a specific minimum amount of PHPCoin.
*   **Coin Maturity:** Your coins must have been held in your wallet for a certain number of blocks without being spent.

If you meet these requirements, your address is automatically entered into a lottery for each new block. The winner is chosen based on a "weight" calculated from your balance and the maturity of your coins. The higher your weight, the higher your chance of winning the staking reward.

## How to Start Staking

To become eligible for staking rewards, you must first send a special "stake" transaction. This is a one-time action that flags your address as a staking participant.

1.  **Open a terminal or command prompt.**
2.  **Navigate to the directory where your PHPcoin wallet is located.**
3.  **Run the following command:**

    ```bash
    php cli/wallet.php send <your-address> 0 "stake"
    ```

    Replace `<your-address>` with your own wallet address. The `0` is the amount to send (this special transaction is free), and `"stake"` is the message that activates your address for staking.

Once this transaction is confirmed, your address will be eligible to receive staking rewards as long as your balance and coin maturity meet the network requirements.

## Technical Details

### Staking Requirements

The requirements to be eligible for staking rewards change as the blockchain grows.

#### 1. Coin Maturity

Your coins must be held for a certain number of blocks before they are considered "mature" for staking.

*   **Before block 290,000:** 600 blocks
*   **At or after block 290,000:** 60 blocks

#### 2. Minimum Balance

You must hold a minimum number of coins to be eligible for staking. This amount changes at specific block heights according to network rules.

*   **Before block 290,000:** 100 PHPCoin
*   **Block 290,001 - 300,000:** 30,000 PHPCoin
*   **Block 300,001 - 400,000:** 40,000 PHPCoin
*   **Block 400,001 - 500,000:** 50,000 PHPCoin
*   **Block 500,001 - 600,000:** 60,000 PHPCoin
*   **Block 600,001 - 700,000:** 80,000 PHPCoin
*   **Block 700,001 - 800,000:** 100,000 PHPCoin
*   **Block 800,001 - 900,000:** 120,000 PHPCoin
*   **Block 900,001 - 1,000,000:** 140,000 PHPCoin
*   **After block 1,000,000:** 160,000 PHPCoin

### Winner Selection

For each block, a stake winner is selected from all eligible accounts based on a `weight` calculated as follows:

`weight = (current_block_height - last_transaction_height) * account_balance`

The account with the highest weight is chosen as the winner. This process is handled by the `getStakeWinner` function in `include/class/Account.php`.

### Staking Rewards

The staking rewards are defined in the `include/rewards.inc.php` file and change at different block heights. For example, between blocks 20,001 and 200,000, the staking reward is 2 PHPCoin per block.
