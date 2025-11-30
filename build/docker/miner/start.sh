#!/bin/bash

CHAIN_ID_FILE="/phpcoin/chain_id"
if [ -f "$CHAIN_ID_FILE" ]; then
    CHAIN_ID=$(cat "$CHAIN_ID_FILE")
else
    CHAIN_ID="00"
fi

ADDRESS="${ADDRESS:-PtD756PoeBfw6KgCLqjpD4sYdZsaFu536F}"
CPU="${CPU:-100}"
NUM_THREADS=$(nproc --all)
THREADS="${THREADS:-$NUM_THREADS}"

MINING_NODE=https://m1.phpcoin.net
if [ "$CHAIN_ID" = "01" ]
then
  MINING_NODE=https://miner1.phpcoin.net
fi
NODE="${NODE:-$MINING_NODE}"

php /phpcoin/utils/miner.php $NODE $ADDRESS $CPU --threads=$THREADS
