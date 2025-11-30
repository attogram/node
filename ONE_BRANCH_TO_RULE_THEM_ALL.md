# One Branch to Rule Them All

This branch refactors the PHPCoin codebase to support multiple networks (mainnet and testnet) from a single `main` branch. The active network is determined by the contents of a `./chain_id` file.

## What was done

*   **Multi-network support:** The codebase was refactored to support multiple networks from a single branch.
*   **`chain_id` file:** The `./chain_id` file is now the source of truth for determining the active network. `00` for mainnet, `01` for testnet.
*   **Network-specific configurations:** Per-chain files for config, checkpoints, rewards, coinspecs, and genesis have been created.
*   **Dynamic loading:** The code now dynamically loads the correct network-specific files based on the `chain_id`.
*   **Refactored network identification:** The code has been refactored to use `CHAIN_ID` instead of `NETWORK` for network identification.
*   **Updated scripts:** The `install_node.sh`, `build/docker/miner/start.sh`, and `build/docker/node/docker_start.sh` scripts have been updated to be driven by the `chain_id` file.
*   **Safe defaults:** The system now safely defaults to mainnet if the `chain_id` file is missing or invalid.

## Updating Existing Installations

### From `main` (mainnet)

1.  **Back up the database:**
    ```bash
    sudo mysqldump phpcoin > phpcoin_backup.sql
    ```
2.  **Back up the application directory:**
    ```bash
    sudo cp -a /var/www/phpcoin-mainnet /var/www/phpcoin-mainnet.bak
    ```
3.  **Move the installation directory:**
    ```bash
    sudo mv /var/www/phpcoin-mainnet /var/www/phpcoin
    ```
4.  **Update the code:**
    ```bash
    cd /var/www/phpcoin
    git pull origin one_branch_to_rule_them_all
    ```
5.  **Rename the database:**
    ```bash
    sudo mysql -e "RENAME DATABASE phpcoin TO phpcoin_00;"
    ```
6.  **Create the `chain_id` file:**
    ```bash
    echo "00" > chain_id
    ```
7.  **Handle the configuration file:**
    ```bash
    cd /var/www/phpcoin
    mv config/config.inc.php config/config.inc.php.bak
    cp config/config-sample.inc.php config/config.inc.php
    ```
    *After this, you will need to manually copy your old settings from `config.inc.php.bak` to the new `config.inc.php`.*
8.  **Re-run the install script to update configurations:**
    ```bash
    cd /var/www/phpcoin
    sudo bash scripts/install_node.sh
    ```

### From `test` (testnet)

1.  **Back up the database:**
    ```bash
    sudo mysqldump phpcoin > phpcoin_backup.sql
    ```
2.  **Back up the application directory:**
    ```bash
    sudo cp -a /var/www/phpcoin-testnet /var/www/phpcoin-testnet.bak
    ```
3.  **Move the installation directory:**
    ```bash
    sudo mv /var/www/phpcoin-testnet /var/www/phpcoin
    ```
4.  **Update the code:**
    ```bash
    cd /var/www/phpcoin
    git pull origin one_branch_to_rule_them_all
    ```
5.  **Rename the database:**
    ```bash
    sudo mysql -e "RENAME DATABASE phpcoin TO phpcoin_01;"
    ```
6.  **Create the `chain_id` file:**
    ```bash
    echo "01" > chain_id
    ```
7.  **Handle the configuration file:**
    ```bash
    cd /var/www/phpcoin
    mv config/config.inc.php config/config.inc.php.bak
    cp config/config-sample.inc.php config/config.inc.php
    ```
    *After this, you will need to manually copy your old settings from `config.inc.php.bak` to the new `config.inc.php`.*
8.  **Re-run the install script to update configurations:**
    ```bash
    cd /var/www/phpcoin
    sudo bash scripts/install_node.sh
    ```

## What needs to be tested

### 1. Installation

*   **`install_node.sh`**
    *   Run the script with a `chain_id` file containing `00` and verify that a mainnet node is installed.
        *   Check that the `phpcoin_00` database is created.
        *   Check that the nginx configuration is created for port 80.
        *   Check that the mainnet blockchain is imported.
    *   Run the script with a `chain_id` file containing `01` and verify that a testnet node is installed.
        *   Check that the `phpcoin_01` database is created.
        *   Check that the nginx configuration is created for port 81.
        *   Check that the testnet blockchain is imported.
    *   Run the script without a `chain_id` file and verify that a mainnet node is installed by default.

### 2. Docker

*   **Node**
    *   Build and run the node container with `CHAIN_ID=00` and verify that a mainnet node is started.
    *   Build and run the node container with `CHAIN_ID=01` and verify that a testnet node is started.
*   **Miner**
    *   Build and run the miner container with a `chain_id` file containing `00` and verify that it connects to the mainnet mining node.
    *   Build and run the miner container with a `chain_id` file containing `01` and verify that it connects to the testnet mining node.

### 3. Application Logic

*   **Database Connection**
    *   Verify that the application connects to the `phpcoin_00` database when `chain_id` is `00`.
    *   Verify that the application connects to the `phpcoin_01` database when `chain_id` is `01`.
*   **Configuration Loading**
    *   Verify that the correct `config.XX.php`, `checkpoints.XX.php`, `rewards.XX.php`, `coinspec.XX.php`, and `genesis.XX.php` files are loaded based on the `chain_id`.
*   **Network-specific Logic**
    *   Verify that all logic that was previously dependent on the `NETWORK` constant now correctly uses the `CHAIN_ID` constant.
    *   Specifically check the following files:
        *   `include/class/Blockchain.php`
        *   `include/class/Transaction.php`
        *   `web/apps/common/include/top.php`
        *   `web/apps/explorer/address.php`

## Auto-update

The auto-update feature is compatible with the One Branch system. The `chain_id` of the node will be preserved during the auto-update process.
