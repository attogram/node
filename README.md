[<img src="https://phpcoin.net/img/logo.svg" width="64"/>](https://phpcoin.net)
# PHPCoin
### Pure PHP blockchain built for Web

[Website](https://phpcoin.net/) | [X (Twitter)](https://x.com/phpcoin) | [Telegram](https://t.me/PHPcoincn) | [Discord](https://discord.gg/2H2YvFexQq) | [Whitepaper](https://docs.google.com/document/d/1zb3j1Gyz0i40Iydnt_1Bz532Jqxw-crpGAWZSqYeflg/edit)

---

## About PHPCoin

PHP Coin is a revolutionary cryptocurrency built entirely on its own blockchain, developed using PHP, one of the world’s most widely adopted programming languages. Unlike tokens dependent on existing blockchains, PHP Coin operates independently, ensuring faster transactions, lower fees, and total control over the network.

By harnessing the power of PHP, PHP Coin demonstrates the versatility of the language beyond traditional web development, paving the way for blockchain innovation that’s accessible, efficient, and reliable.

## Features

*   **Open and Easy**: PHP Coin is fully open-source and written in PHP, making it accessible for developers worldwide to learn, contribute, and innovate.
*   **Lightweight**: Designed for efficiency, PHP Coin operates with minimal resources, ensuring fast transactions and low energy consumption.
*   **Built for the Web**: Optimized for seamless integration into web platforms, PHP Coin bridges blockchain technology with the dynamic world of web development.

## How to Get Started

1.  **Set Up Your Wallet**: Create a PHP Coin wallet [online](https://main1.phpcoin.net/dapps.php?url=PeC85pqFgRxmevonG6diUwT4AfF7YUPSm3/wallet) or download the official wallet for [Windows](https://phpcoin.net/download/phpcoin-wallet-win.exe) and [Linux](https://phpcoin.net/download/phpcoin-wallet-linux) to securely manage your coins.
2.  **Acquire PHP Coin**: You can acquire PHP Coin by [mining](https://main1.phpcoin.net/dapps.php?url=PeC85pqFgRxmevonG6diUwT4AfF7YUPSm3/miner), buying on centralized exchanges ([KlingEx](https://klingex.io/trade/PHP-USDT?ref=3436CA42)), or trading on decentralized exchanges ([QuickSwap @ Polygon](https://quickswap.exchange/#/swap?currency0=0xc2132D05D31c914a87C6611C10748AEb04B58e8F&currency1=0x006E1D324FA995f1c1B8318b058Ae9c117A72c20&swapIndex=0)).
3.  **Stay Connected**: Join our community on [X](https://twitter.com/phpcoin), [Telegram](https://t.me/PHPcoincn), and [Discord](https://discord.gg/2H2YvFexQq) for updates, support, and to connect with other PHP Coin enthusiasts.

## Node Installation

### Requirements
*   PHP version 7.2+ with extensions: `gmp`, `bcmath`, `curl`, `mysql` or `sqlite`.
*   Web server: Apache or Nginx.
*   Database: MySQL 8.0 or MariaDB 10+.
*   An external, static IP address.

### Automatic Install (Recommended)
This method is intended for clean Linux systems (like a new VPS). It will automatically install all required packages, configure the web server and database, and start the node.

**Mainnet:**
```bash
curl -s https://phpcoin.net/scripts/install_node.sh | bash
```
**Testnet:**
```bash
curl -s https://phpcoin.net/scripts/install_node.sh | bash -s -- --network testnet
```

### Manual Install

#### 1. Update System and Install Packages
```bash
sudo apt update
sudo apt install -y apache2 php libapache2-mod-php php-mysql php-gmp php-bcmath php-curl unzip mariadb-server
```

#### 2. Download PHPCoin Node
```bash
sudo mkdir /var/www/phpcoin
cd /var/www/phpcoin
sudo git clone https://github.com/phpcoinn/node .
```

#### 3. Configure Database
```bash
export DB_NAME=phpcoin
export DB_USER=phpcoin
export DB_PASS=phpcoin # Use a strong, unique password in production

sudo mysql -e "CREATE DATABASE $DB_NAME;"
sudo mysql -e "CREATE USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
sudo mysql -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';"

sudo cp config/config-sample.inc.php config/config.inc.php
sudo sed -i "s/ENTER-DB-NAME/$DB_NAME/g" config/config.inc.php
sudo sed -i "s/ENTER-DB-USER/$DB_USER/g" config/config.inc.php
sudo sed -i "s/ENTER-DB-PASS/$DB_PASS/g" config/config.inc.php
```

#### 4. Configure Web Server (Apache)
Create a new virtual host file `/etc/apache2/sites-available/phpcoin.conf`:
```apache
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/phpcoin/web
    RewriteEngine on
    RewriteRule ^/dapps/(.*)$ /dapps.php?url=$1
</VirtualHost>
```
Enable the new site:
```bash
sudo a2dissite 000-default
sudo a2ensite phpcoin
sudo service apache2 restart
```

#### 5. Finalize Setup
```bash
sudo mkdir tmp dapps
sudo chown -R www-data:www-data .
```
To initialize the blockchain, open the node's IP address in your web browser.

## Resources

*   **Wallets**: [Linux GUI](https://phpcoin.net/download/phpcoin-wallet-linux) | [Windows GUI](https://phpcoin.net/download/phpcoin-wallet-win.exe) | [Web Wallet](https://main1.phpcoin.net/dapps.php?url=PeC85pqFgRxmevonG6diUwT4AfF7YUPSm3/wallet)
*   **Miners**: [Linux CLI](https://phpcoin.net/download/phpcoin-miner) | [Web Miner](https://main1.phpcoin.net/dapps.php?url=PeC85pqFgRxmevonG6diUwT4AfF7YUPSm3/miner)
*   **Explorer**: [Mainnet Explorer](https://main1.phpcoin.net/apps/explorer/)
*   **Dapps**: [PHPCoin Dapps](https://main1.phpcoin.net/dapps.php?url=PeC85pqFgRxmevonG6diUwT4AfF7YUPSm3/)
*   **Community**: [Bitcointalk](https://bitcointalk.org/index.php?topic=5371138.0)

## Technical Specifications

| Item                  | Details                                            |
| --------------------- | -------------------------------------------------- |
| **Name**              | PHPCoin                                            |
| **Symbol**            | PHP                                                |
| **Block Time**        | 60 seconds                                         |
| **Consensus**         | EPOW (Elapsed Proof of Work)                       |
| **Address Generation**| ECDSA + RIPEMD160 + 3xSHA256                       |
| **Address Prefix**    | `P`                                                |
| **Mining Algorithm**  | Argon2 (POW), SHA256 (Hashing)                     |
| **Chain ID**          | `00` (Mainnet)                                     |
| **Genesis Block**     | 2023-04-01 12:00:00                                |
| **Premine**           | 103,200,000 (from Waves token swap)                |
| **Total Supply**      | 203,199,990                                        |
| **Masternode**        | 10,000 PHP Collateral                              |
| **Staking**           | Min 100 PHP, 600 block maturity                    |

## Roadmap

*   **Q3 2021**: Project startup and testnet launch.
*   **Q1 2023**: Mainnet launch and token swap.
*   **Q3 2024**: Ecosystem expansion and ERC20 token creation.
*   **Q1 2025**: Cross-chain integration for interoperability.
*   **Q3 2025**: Launch of a native PHPCoin Decentralized Exchange (DEX).
*   **Q1 2026**: Expansion into NFTs and Web3 applications.
*   **Beyond**: Continued ecosystem growth and strategic partnerships.
