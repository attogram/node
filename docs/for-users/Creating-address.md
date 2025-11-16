# Creating PHPCoin address

**Address** is basic identifier on PHPCoin network.

It is public and open to everyone, but protected with private key in user possession.

Address can be created im many different ways:
* [in online wallet](#online-wallet)
* [in GUI wallet](#gui-wallet)
* [in CLI wallet](#cli-wallet)
* [through PHP code](#code-wallet)
* [through node API](#api-wallet)

## Create address in online wallet <a name="online-wallet"></a>

Online wallet is available as decentralized app (dapp) on every node.

As an example for main node it can be accessed on link:

[https://wallet.phpcoin.net/apps/wallet](https://wallet.phpcoin.net/apps/wallet).

Visit wallet and click on link "Authenticate" and then "Create new one".

Note and copy (or write down) generated data.

**Most important is that generated private key is never given or revealed to other party.
It must be stored securely somewhere.**

**Without private key account can not be restored.**

## GUI wallet <a name="gui-wallet"></a>

[GUI wallet](gui-wallet) is standalone desktop application with versions for Windows and Linux.

With starting wallet if not found it will create account and address and store it in user system.

Address is available and visible in header of application.

## CLI wallet <a name="cli-wallet"></a>

[CLI wallet](cli-wallet) is application without user interface which is executed in console.

It is available only for Linux (Ubuntu 20.04)
on [link](https://phpcoin.net/download/phpcoin-wallet)

After downloading wallet:

`wget https://phpcoin.net/download/phpcoin-wallet`

make it executable:

`chmod +x phpcoin-wallet`

and start it with command:

`./phpcoin-wallet`

On start, wallet with automatically create account and address.

Account data is then stored on user system.

If user wants wallet can be encrypted also.


## Generate address through code <a name="code-wallet"></a>

While standalone CLI or GUI wallet are only available for supported systems,
account can be also created on any system that is able to run php.

User need to download or clone PHPCoin source code.

```shell
git clone https://github.com/phpcoinn/node
```

Then wallet can be accessed through command

```shell
cd node/utils
php wallet.php
```
Or simple executing PHP code using [PHPCoin SDK](phpcoin-sdk)

```php
require_once './utils/sdk.php';
$account = Account::generateAcccount();
print_r($account);
```


## Generate address through node API <a name="api-wallet"></a>

On each node is available [API](api) which can be used to generate account.

Simply visit url:

[https://main1.phpcoin.net/api.php?q=generateAccount](https://main1.phpcoin.net/api.php?q=generateAccount)

and write down generated data.
