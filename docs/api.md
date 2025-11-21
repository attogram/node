# Node API

Each installed and running node has built in api service which can be used to interact with blockchain
using simple API rest calls.

All API endpoints are public, so there is no need for any authentication.

For example main mode API is available on:

https://main1.phpcoin.net/api.php?q=version

Complete API specification is subject to change and its documentation is available on url:

https://main1.phpcoin.net/doc

## Configuration

API is by default enabled on every node, but node owner can disable it in [configuration](node-configuration).

There are few config options to handle that

```php
// Allow others to connect to the node api (if set to false, only the below 'allowed_hosts' are allowed)
$_config['public_api'] = true;

// Hosts that are allowed to mine on this node
$_config['allowed_hosts'] = ['*'];```
