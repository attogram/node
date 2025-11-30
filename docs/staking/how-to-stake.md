[PHPCoin Docs](../) > [Staking](./) > How to Stake

---

# How to Stake

## Introduction

Staking is the process of holding PHPCoin in your wallet to support the operations of the blockchain network. In return for holding coins, you receive rewards in the form of new PHPCoin. It's a great way to earn passive income while helping to secure the network.

## How Staking Works

PHPcoin uses a Proof-of-Stake (PoS) consensus algorithm. This means that anyone who holds PHPCoin can receive rewards. Unlike miners or block generators, **stakers do not create or validate blocks**. Instead, for each block created, a "stake winner" is chosen from all eligible stakers to receive a reward.

To be eligible for staking rewards, you must meet two main requirements:

*   **Minimum Balance:** You must hold a specific minimum amount of PHPCoin.
*   **Coin Maturity:** Your coins must have been held in your wallet for a certain number of blocks without being spent.

If you meet these requirements, your address is automatically entered into a lottery for each block. The winner is chosen based on a "weight" calculated from your balance and the maturity of your coins. The higher your weight, the higher your chance of winning the staking reward.

## How to Start Staking

To become eligible for staking rewards, you must first signal your intent by sending a special "stake" transaction. This is a one-time action.

1.  **Open a terminal or command prompt.**
2.  **Navigate to the directory where your PHPcoin wallet is located.**
3.  **Run the following command:**

    ```bash
    php cli/wallet.php send <your-address> 0 "stake"
    ```

    Replace `<your-address>` with your own wallet address. The `0` is the amount of PHPCoin to send (this special transaction is free), and `"stake"` is the message that activates your address for staking.

Once this transaction is confirmed, your address will be eligible to receive staking rewards as long as your balance and coin maturity meet the network requirements.

## Technical Details

### Proof-of-Stake Winner Selection

For each block, a stake winner is selected from all eligible accounts. The selection is based on a `weight` calculated as follows:

`weight = (current_block_height - last_transaction_height) * account_balance`

The account with the highest weight is chosen as the winner. This process is handled by the `getStakeWinner` function in `include/class/Account.php`.

### Staking Rewards

The staking rewards are defined in the `include/rewards.inc.php` file. The reward amount changes at different block heights. For example, between blocks 20,001 and 200,000, the staking reward is 2 PHPCoin.

### Staking Requirements

The requirements to be eligible for staking are defined in `include/class/Blockchain.php` and `include/coinspec.inc.php`.

*   **Maturity:** Your coins must be held for a certain number of blocks before they are considered mature.
    *   **Before block 290,000:** 600 blocks
    *   **At or after block 290,000:** 60 blocks

*   **Minimum Balance:** You must hold a minimum number of coins to be eligible.
    *   **Before block 290,000:** 100 PHPCoin
    *   **At or after block 290,000:** The minimum balance is twice the current masternode collateral. For example:
        *   From block 290,001 to 300,000, the masternode collateral is 15,000 PHPCoin, so the minimum staking balance is 30,000 PHPCoin.
        *   From block 300,001 to 400,000, the masternode collateral is 20,000 PHPCoin, so the minimum staking balance is 40,000 PHPCoin.
