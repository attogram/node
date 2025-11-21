# Requirements

* PHP version 7.2 with extensions: gmp, bcmath, curl, mysql or sqlite
* Web server: Apache or Nginx
* Database: Mysql 8.0 or MariaDb 10
* External IP address

# Installation

## Automatic install

Automatic install is intended for clean systems, for example on Linux VPS (Virtual Private Server)

Open terminal and execute following script:

    curl -s https://phpcoin.net/scripts/install_node.sh | bash

This script with automatically download all required packages, install Apache web server and MariaDb database, download node source, configure and start.

For installing testnet node execute following sript:
```
curl -s https://phpcoin.net/scripts/install_node.sh | bash -s -- --network testnet
```
What this script does is explained in detail on Manual install


## Docker container

Docker images are avaialble on https://hub.docker.com/u/phpcoin

## Manual install

### Update system
    apt update
### Install necessary software packages, PHP with modules
    apt install apache2 php libapache2-mod-php php-mysql php-gmp php-bcmath php-curl unzip -y
    apt install mariadb-server -y
### Download and install PHPCoin node
```
mkdir /var/www/phpcoin
cd /var/www/phpcoin
git clone https://github.com/phpcoinn/node .
```
### Install and configure web server

Chose which server you want to use and execute following script accordingly:

#### Apache (recommended)

Create new Apache virtual host config `/etc/apache2/sites-available/phpcoin.conf` with content:

```
<VirtualHost *:80>
        ServerAdmin webmaster@localhost
        DocumentRoot /var/www/phpcoin/web
        RewriteEngine on
        RewriteRule ^/dapps/(.*)$ /dapps.php?url=$1
</VirtualHost>
```
Disable default site and enable new config:
```
a2dissite 000-default
a2ensite phpcoin
service apache2 restart
```
#### Nginx
For nginx here is server configuration:
```
server {
    listen 80;
    server_name default_server;
    root /var/www/phpcoin/web;

    index index.html index.htm index.php;

    rewrite ^/dapps/(.*)$ /dapps.php?url=$1 break;

    location / {
        try_files $uri $uri/ =404;
    }

    location ~ \.php$ {
        add_header X-uri "$uri";
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
     }

    location ~ /\.ht {
        deny all;
    }
}
```

### Install and configure database server
#### Mariadb
Install Mariadb server package and php extension:

    apt install mariadb-server php-mysql -y
Define DB settings:
```
export DB_NAME=phpcoin
export DB_USER=phpcoin
export DB_PASS=phpcoin
```
Create database, user and grant privileges:
```
mysql -e "create database $DB_NAME;"
mysql -e "create user '$DB_USER'@'localhost' identified by '$DB_PASS';"
mysql -e "grant all privileges on $DB_NAME.* to '$DB_USER'@'localhost';"
```
Create and update phpcoin config file:
```
cd /var/www/phpcoin
cp config/config-sample.inc.php config/config.inc.php
sed -i "s/ENTER-DB-NAME/$DB_NAME/g" config/config.inc.php
sed -i "s/ENTER-DB-USER/$DB_USER/g" config/config.inc.php
sed -i "s/ENTER-DB-PASS/$DB_PASS/g" config/config.inc.php
```

### Configure PHP coin node and start
Set web server folders and permissions:
```
mkdir tmp
mkdir dapps
chown -R www-data:www-data .
```
Get external IP and open once web page to initialize blockchain:
```
export IP=$(curl -s http://whatismyip.akamai.com/)
curl "http://$IP" > /dev/null 2>&1
```

#### Setup node miner

1. Wait node to fully sync blockchain
2. Go to [wallet](https://wallet.testnet.phpcoin.net/apps/wallet) and generate new address. Write down public key and private key.
3. Edit node config file ```config/config.inc.php``` and enter keys in config section for miner:
```
$_config['miner']=false;
$_config['miner_public_key']="";
$_config['miner_private_key']="";
```
After node is synced and there are peers conencted to node miner will start as server process.

This miner will receive 30% of block reward if mine a new block.

#### Setup node generator

Similar as node miner, node generator can also be enabled through config file

```
$_config['generator']=false;
$_config['generator_public_key']="";
$_config['generator_private_key']="";
```

It can be used same public/private key as for miner or generate different one.

By enabling generator node will be available for standalone client miners and will be used to validate submitted blocks.

Generator will receive 10% of block reward if connected miner mine a new block.

#### Node automatic update

Install script will setup automatic node update process if there is new version on github.

Update script is job which periodically check for update and execute git pull

Admin can start node update manually executing util command:

```
php cli/util.php update
```
