Decentralized apps are one of the main features of the PHP Coin network.

They are built on top of PHP coin nodes and will allow node owners to build their own apps.

These apps are decentralized because they will run on any node on the network and any change from owner will be propagated through all other active nodes on the network.

Dapps are isolated from underlying nodes and run in their own environment.

Because of security they need to use a limited set of functions but enough for building any useful application.

One example of their purpose, dapps can represent frontend for interacting with smart contracts.

## Setup and enabling dapps

In order to allow node to publish its own decentralized apps there is a section in the config file to enable it.

```
/**
 * Configuration for decentralized apps
 */
$_config['dapps']=false;
$_config['dapps_public_key']="";
$_config['dapps_private_key']="";
```

Similar as for other configurations, generate a new account and write down its public and private key.

That address will be the address of your app folder on the network.

Then execute command:

```
php cli/util.php propagate-dapps
```

This will create a new folder named with dapps address, and set permissions.

All files for your app you can create and update in this folder.

## Updating and publishing

To publish your app dapps address needs to be verified.

Then on every update of files in your dapps folder will be calculated hash and signature and it will be propagated to the network.

Other nodes will check signature with your dapps public key and accept to update its own files.

Publishing changes is done automatically but also can be done manually with command:

```
php cli/util.php propagate-dapps
```

Then your dapp will be available on address:

`<node_ip_or_hostname>/dapps.php?id=<dapps_address>/<path_to file>`

or:

`<node_ip_or_hostname>/dapps/<dapps_address>/<path_to file>`**

and after few moments on any other node on network.

Example:

[https://node1.phpcoin.net/dapps.php?url=PeC85pqFgRxmevonG6diUwT4AfF7YUPSm3/hello.php](https://node1.phpcoin.net/dapps.php?url=PeC85pqFgRxmevonG6diUwT4AfF7YUPSm3/hello.php)

[https://node1.phpcoin.net/dapps/PeC85pqFgRxmevonG6diUwT4AfF7YUPSm3/hello.php](https://node1.phpcoin.net/dapps/PeC85pqFgRxmevonG6diUwT4AfF7YUPSm3/hello.php)**

** For second url in order to work Rewrite engine must be enabled and configured on node server.
For new nodes it will be created automaticaly, but for older ones following lines need to be added to Apache config:
/etc/apache2/sites-available/phpcoin.conf

```
RewriteEngine on
RewriteRule ^/dapps/(.*)$ /dapps.php?url=$1
```

## Updating and downloading

Node does not need to have a configured address to accept hosting of other apps.

Just execute this command to create a main folder (for older nodes). Newer will have that folder created already.

```
php cli/util.php propagate-dapps
```

For other nodes who want to update other apps it is also done automatically when node owners push their apps or can be do manually with command:

```
php cli/util.php download-dapps <dapps_address>
```

## Functions

Because app is isolated from underlying node it can not access to its system, so for some of standard functionality there are helper functions which can be used in apps code.

These functions are located in file `dapps.functions.php` and can be used in code.

As functionlaity extends there will be more and more functionality built in dapps execution.
