# Node Configuration Guide

This guide provides a comprehensive overview of the configuration system for your node. Proper configuration is essential for the security, performance, and functionality of your node.

## The Configuration System

The node uses a multi-file system to manage its configuration, ensuring that your custom settings are not overwritten during updates.

-   **`config/config.default.php`**: This file contains all the default settings for the node. **You should not edit this file directly.** It serves as a template and a reference for all available configuration options.
-   **`config/config.inc.php`**: This is where you should place your custom settings. Create this file if it doesn't exist. Any setting you define in this file will override the corresponding default setting from `config.default.php`.

To customize your node's configuration, copy the relevant settings from `config.default.php` to `config.inc.php` and modify their values.

---

## General Settings

### `chain_id`
-   **Description**: A unique identifier for the blockchain. This helps differentiate between mainnet, testnet, or private chains.
-   **Default**: The value from the `chain_id` file at the root of the project.
-   **Example**: `$_config['chain_id'] = 'mainnet';`

### `db_connect`
-   **Description**: The database connection string (DSN) for connecting to your MySQL database.
-   **Default**: `'mysql:host=localhost;dbname=phpcoin;charset=utf8'`
-   **Example**: `$_config['db_connect'] = 'mysql:host=127.0.0.1;dbname=my_phpcoin;charset=utf8mb4';`

### `db_user`
-   **Description**: The username for your MySQL database.
-   **Default**: `'phpcoin'`

### `db_pass`
-   **Description**: The password for your MySQL database.
-   **Default**: `'phpcoin'`

---

## API and Peering

### `public_api`
-   **Description**: If `true`, allows any remote host to connect to your node's API. If `false`, only hosts listed in `allowed_hosts` can connect.
-   **Default**: `true`

### `allowed_hosts`
-   **Description**: A list of IP addresses or hostnames that are allowed to mine on this node or connect to the API if `public_api` is `false`. `*` allows all hosts.
-   **Default**: `['*']`
-   **Example**: `$_config['allowed_hosts'] = ['192.168.1.100', 'mynode.local'];`

### `initial_peer_list`
-   **Description**: A list of trusted initial peers to connect to and sync the blockchain from when your node first starts.
-   **Default**: A list of official PHPCoin nodes.

### `passive_peering`
-   **Description**: If `true`, your node will not actively seek out new peers. It will only use the `initial_peer_list` for syncing. This typically requires a cron job on `sync.php`.
-   **Default**: `false`

### `peers_limit`
-   **Description**: The maximum number of peers your node will propagate blocks and transactions to.
-   **Default**: `30`

### `offline`
-   **Description**: If `true`, your node will run in offline mode. It will not send or receive any peer requests.
-   **Default**: `false`

### `proxy`
-   **Description**: If you need to use a proxy for outgoing peer requests, define it here.
-   **Default**: `null`
-   **Example**: `$_config['proxy'] = 'socks5://127.0.0.1:9050';`

### `peer_max_mempool`
-   **Description**: The maximum number of transactions your node will accept into its mempool from a single peer during a sync.
-   **Default**: `100`

### `use_official_blacklist`
-   **Description**: If `true`, your node will block transfers from addresses that have been blacklisted by the PHPCoin developers.
-   **Default**: `true`

### `sync_recheck_blocks`
-   **Description**: The number of recent blocks to re-check during a sync to ensure chain integrity.
-   **Default**: `10`

### `allow_hostname_change`
-   **Description**: Set to `true` only if you need to change your node's public hostname.
-   **Default**: `false`

---

## Logging

### `enable_logging`
-   **Description**: If `true`, enables logging to the file specified in `log_file`.
-   **Default**: `true`

### `log_file`
-   **Description**: The path to the log file. This file should not be publicly accessible via the web.
-   **Default**: `'tmp/phpcoin.log'`

### `log_verbosity`
-   **Description**: The level of detail for logs. `0` is the default, and `5` is the most verbose.
-   **Default**: `0`

### `server_log`
-   **Description**: If `true`, logs will be sent to the web server's error log (e.g., Apache's `error_log`).
-   **Default**: `false`

---

## Miner and Generator

### `miner`
-   **Description**: Set to `true` to enable the mining functionality on your node.
-   **Default**: `false`

### `miner_public_key` / `miner_private_key`
-   **Description**: The public and private keys for your miner. The public key is used to receive mining rewards.
-   **Default**: `""`

### `miner_cpu`
-   **Description**: The number of CPU cores to use for mining. `0` attempts to auto-detect.
-   **Default**: `0`

### `generator`
-   **Description**: Set to `true` to enable block generation (forging).
-   **Default**: `false`

### `generator_public_key` / `generator_private_key`
-   **Description**: The public and private keys for your block generator.
-   **Default**: `""`

---

## Node Administration

### `admin`
-   **Description**: Set to `true` to enable the web-based administration panel.
-   **Default**: `false`

### `admin_password`
-   **Description**: The password for the web admin panel.
-   **Default**: `''`

### `admin_public_key`
-   **Description**: Alternatively, you can specify a public key to log in to the admin panel using its corresponding private key.
-   **Default**: `''`

---

## Masternode

### `masternode`
-   **Description**: Set to `true` to enable masternode functionality.
-   **Default**: `false`

### `masternode_public_key` / `masternode_private_key`
-   **Description**: The public and private keys for your masternode.
-   **Default**: `""`

---

## Dapps (Decentralized Apps)

### `dapps`
-   **Description**: Set to `true` to enable support for Dapps.
-   **Default**: `false`

### `dapps_public_key` / `dapps_private_key`
-   **Description**: The public and private keys for your Dapps identity.
-   **Default**: `""`

### `dapps_anonymous`
-   **Description**: If `true`, allows Dapps to be used anonymously.
-   **Default**: `false`

### `dapps_disable_auto_propagate`
-   **Description**: If `true`, disables the automatic propagation of Dapps data.
-   **Default**: `true`

---

## Homepage Customization

### `homepage_apps`
-   **Description**: An array that controls the clickable application blocks on the node's homepage. Full details on how to customize this are provided in the "Homepage Configuration" section of this guide.
-   **Default**: An array containing "Explorer", "Miner", "Dapps", "Exchange", and "Docs".
