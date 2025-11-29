# PHPCoin node on docker

You can run your own phpcoin node using docker.

This will run a full node, with miner enabled, open api, and admin panel.

To run a mainnet node:
docker run -itd --name phpcoin-main -p 80:80 -e EXT_PORT=80 -e CHAIN_ID=00 -v phpcoin-config-main:/var/www/phpcoin/config phpcoin/node

To run a testnet node:
docker run -itd --name phpcoin-test -p 91:80 -e EXT_PORT=91 -e CHAIN_ID=01 -v phpcoin-config-test:/var/www/phpcoin/config phpcoin/node

This will map the config folder to a volume, so you can keep the node configuration.
You can check the node logs by running:
docker logs -f phpcoin-main

To enter the node shell, run:
docker exec -it phpcoin-main bash
