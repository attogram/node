# Verifying address

For every user to start mining PHPCoin (except pool mining) he need to have created and validated address on blockchain.

To create address check [Create new address](Creating-address).

Each address on blockchain is stored in accounts table with address and public key.
When first time is address appear on blockchain it is unverified (without public key). For example if someone make transaction first time to that address.

When that address makes some outgoing transaction it stores its public key in accounts table and becomes verified.

Address can be verified in few ways:

## Using web faucet dapp

1. Go to faucet dapp: https://faucet.phpcoin.net/apps/faucet/
2. Enter your address, fill captcha, and click on button Receive
3. Wait for transaction to be added to blockchain
4. Log in to address wallet
5. Send on transaction to some address (for example back to faucet: PYcFC7BvhJ4queNMwbDuxBGdqSmBvyjXZT)
6. Wait for transaction to be added to blockchain

## Using verifier dapp

1. Open verifier dapp link: https://main1.phpcoin.net/dapps.php?url=PeC85pqFgRxmevonG6diUwT4AfF7YUPSm3/verifier.
2. Authenricate or generate new address
3. Click on request to receive some amount from verifier address.
4. Wait transaction to complete and click Next
5. After that approve new transaction to send back to verifier
6. Wait transaction to complete

## From GUI wallet

1. Open GUI wallet
2. Click on menu Verify...
3. Follow instructions
