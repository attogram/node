Here are instructions for starting masternode from GUI wallet.

## Requirements

You need a GUI wallet with an address with more than current masternode collateral in balance.

Install node on remote server.

## Open main wallet

Open wallet. Your address shows balance.
First save this wallet to file so you can access it later.

Go to File -> Save. File dialog will open. Enter name of the file (for example my_wallet.dat) and click save.

This is your main wallet

## Setup masternode wallet

Each masternode needs to have its own node with public ip and masternode address.

From the wallet go to File -> New. File dialog will open. Enter the name of the wallet (for example my_masternode.dat) and click Save.

That will be your masternode wallet.

Go to receive tab, and copy masternode address public key and private key. You will need it later to configure node server.

Automatically you will be switched to this wallet.

Balance will be 0 and address will be unverified.

## Verify masternode address

Next you need to verify the wallet address.
First copy address.

Go to the faucet url in the browser, enter your address, fill captcha text and click on receive.

This will create a transaction on blockchain to transfer 0.001 coin to your masternode address.

Go back to your wallet in the home tab, you will see the pending transaction soon. Wait one block until the transaction becomes confirmed.

Then you will have 0.001 coin on your balance.

Next step is to send some amount from the wallet address so it will be verified. Go to send, enter receiver address (you can send back to faucet address, PYcFC7BvhJ4queNMwbDuxBGdqSmBvyjXZT), enter any amount (you can send all 0.001) and click send.

A new transaction will be created and submitted to the network. At the home tab you will see a pending transaction.

Wait one block until the transaction becomes confirmed.

Your address is now verified.

Alternatively, you can use verifier dapp

https://main1.phpcoin.net/dapps.php?url=PeC85pqFgRxmevonG6diUwT4AfF7YUPSm3/verifier

where you can in easy process complete verification process.

## Create masternode

When your masternode address is verified, you need to switch back to your main address.

Click File -> open, select previous wallet (my_wallet.dat) and click open.

Wallet will now load your address with balance.

Go to masternodes and in the right section create masternode, enter masternode address and click on create.

If all conditions are satisfied new special transaction will be created and submitted to the network.

This transaction will transfer collateral amount from your main wallet to the masternode address.

In the home tab there will be a new pending transaction. Wait for one block until the transaction becomes confirmed.

You will see that your wallet balance is now reduced by collateral amount and in the masternodes tab in the right section there will be an entry with your new masternode address and balance.

## Configure masternode on node server

Now you need to go to your node server and configure the masternode address.

Log in to your server.

Open node config file (default: /var/www/phpcoin/config/config.inc.php) and setup masternode keys and enable masternode:

```
$_config['masternode']=true;
$_config['masternode_public_key']="...";
$_config['masternode_private_key']="...";
```

After that masternode will start receiving rewards.

## Removing masternode

Now you can switch to your masternode wallet. Go to file -> open, select other file ( my_masternode.dat) and click open.

Now the wallet will load your masternode address and you will see current balance.

In tab masternodes you will see in the right section that this wallet is masternode and  a now button to remove this masternode.

You can remove the masternode only if it was running more than 30 days.

Enter payout address. This can be any address or your previous wallet address.

A new special transaction will be created and submitted to the network.

This transaction will reduce your masternode address by collateral amount and send them to the payout address.

When a transaction is confirmed, masternode will be removed from the list and stop receiving rewards.
