[PHPCoin Docs](../) > [Staking](./) > How to Stake

---

# How to Stake

## Introduction

Staking is the process of holding PHPcoin in your wallet to support the operations of the blockchain network. In return for staking, you receive rewards in the form of new PHPcoin. It's a great way to earn passive income while helping to secure the network.

## How Staking Works

PHPcoin uses a Proof-of-Stake (PoS) consensus algorithm. This means that anyone who holds PHPcoin can participate in the process of creating new blocks and validating transactions.

To be eligible for staking rewards, you need to meet the following requirements:

*   **Minimum Balance:** You must have a minimum balance of PHPcoin in your wallet.
*   **Maturity:** Your coins must have reached a certain maturity, which means they must have been held in your wallet for a certain number of blocks without being spent.

If you meet these requirements, your wallet is eligible to be chosen to create the next block. If your wallet is chosen, you will receive a reward in the form of new PHPcoin.

## How to Start Staking

To start staking, you need to send a special "stake" transaction. This is done using the command-line wallet.

1.  **Open a terminal or command prompt.**
2.  **Navigate to the directory where you installed PHPcoin.**
3.  **Run the following command:**

    ```bash
    php cli/wallet.php send <your-address> 0 "stake"
    ```

    Replace `<your-address>` with your own wallet address. The `0` is the amount of PHPcoin to send (staking transactions do not require sending any coins), and `"stake"` is the message that tells the network you want to start staking.

Once you have sent the "stake" transaction, your wallet will be eligible to receive staking rewards as long as it is running and connected to the network.

## Technical Details

### Proof-of-Stake

PHPcoin's Proof-of-Stake (PoS) algorithm is a system that allows users to earn rewards by holding coins and participating in the network. The more coins you hold, the higher your chances of being selected to create a new block and receive a reward.

### Staking Rewards

The staking rewards are defined in the `include/rewards.inc.php` file. The reward amount changes at different block heights. For example, between blocks 20,001 and 200,000, the staking reward is 2 PHPcoin per block.

### Staking Requirements

The staking requirements are defined in the `include/class/Blockchain.php` file.

*   **Maturity:** The maturity requirement is the number of blocks that your coins must be held in your wallet before they are eligible for staking. This is currently set to 600 blocks, but is reduced to 60 blocks after block height `UPDATE_11_STAKING_MATURITY_REDUCE`.
*   **Minimum Balance:** The minimum balance requirement is the minimum amount of PHPcoin that you must hold in your wallet to be eligible for staking. This is currently set to 100 PHPcoin, but changes to be twice the masternode collateral after block height `UPDATE_12_STAKING_DYNAMIC_THRESHOLD`.

You can always check the latest staking requirements by examining the `include/class/Blockchain.php` file.
