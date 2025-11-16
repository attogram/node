## Create masternode address

[Create](Creating-address.md) and [verify](Verifying-address.md) new account which address will identify masternode on network.

## Configure node as masternode

[Install](../for-developers/Node-installation.md) and configure node.

Wait node to sync and appear on network (in peers).

Open node config file (default: /var/www/phpcoin/config/config.inc.php) and setup masternode keys and enable masternode:

```
$_config['masternode']=true;
$_config['masternode_public_key']="...";
$_config['masternode_private_key']="...";
```

## Start masternode

Open wallet cli or standalone and execute command:

```
php utils/wallet.php masternode-create <masternode_address>
```
This command will create special type of transaction which will send collateral amount from wallet to masternode address.

New transaction will be added to mempool and mined in next block.

After that masternode will be added to list and start receiving rewards.

## Check masternode

You can check your masternode status in list in any of peers or in admin page of node.

## Remove masternode

Masternode can be removed only if was running some time (30 days).
For removing masternode you must open wallet for masternode account and execute command:

```
php utils/wallet.php masternode-remove <payoutaddress>
```
