# PHPCoin miner on docker

You can run the phpcoin miner using docker.

-e ADDRESS=<YOUR_ADDRESS> \
-e NODE=<NODE_URL> \
-e CPU=<CPU_PERCENT> \
-e THREADS=<THREADS> \
-v /path/to/your/phpcoin/chain_id:/phpcoin/chain_id \
phpcoin-miner

Explanation of parameters:

ADDRESS: your address where you will receive the mining reward
NODE: the url of the node where you are mining
CPU: the percent of cpu to be used for mining
THREADS: number of threads to be used for mining
/path/to/your/phpcoin/chain_id: the path to your `chain_id` file. This is used to determine whether to mine on mainnet or testnet.
