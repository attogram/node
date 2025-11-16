For faster syncing with network node admin can download blockchain snapshot and manualy import all blocks.

Blockchain snapshot is created daily on main node.


Download latest snapshot file from: https://phpcoin.net/download/blockchain.zip

(_For testnet file is: https://phpcoin.net/download/blockchain-testnet.zip_)

```
cd /var/www/phpcoin
wget https://phpcoin.net/download/blockchain.zip
```

Extract zip archive:

```
unzip blockchain.zip
```

and import blockchain:

```
cd /var/www/phpcoin
php cli/util.php importchain blockchain.txt
rm blockchain.txt
rm blockchain.zip
```
Blockchain will be updated from current node block.

If want to import from start, before import clear all database with:

```
php cli/util.php clean
```

## Restore blockchain database

Blockchain for PHPCoin is stored in MySQL database, so it can be easy restored on node

Download latest backup file from: https://phpcoin.net/download/blockchain.sql.zip

(_For testnet archive file is: https://phpcoin.net/download/blockchain-testnet.sql.zip_)

```
cd /var/www/phpcoin
wget https://phpcoin.net/download/blockchain.sql.zip
```
Extract zip archive:

```
unzip blockchain.sql.zip
```
and restore blockchain:
```
cd /var/www/phpcoin
php cli/util.php importdb blockchain.sql
rm blockchain.sql
rm blockchain.sql.zip
```
Please note that this will restore database as is, without verification.

If you want to check and verify blocks execute command:

```
php cli/util.php verify-blocks
```