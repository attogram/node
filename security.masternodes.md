# PHPCoin Security Analysis: Masternode Attacks

Masternodes in the PHPCoin network have a privileged role that grants them the ability to approve blocks and receive rewards. This analysis examines the potential attacks a malicious masternode or a cartel of masternodes could execute.

## Attack Vector 1: Winner Selection Manipulation (Collusion Attack) - VIABLE

A malicious block generator can collude with a malicious masternode to bypass the deterministic winner selection process and assign the block reward to the colluding masternode, even when they are not the legitimate winner.

### How it Works

1.  **Deterministic Selection:** The `Masternode::getWinner()` function is designed to be a fair, deterministic algorithm that all nodes can run to agree on the rightful winner for a given block height.
2.  **The Flaw (During Block Validation):** When a node validates a new block, the `Masternode::verifyBlock()` function is called. This function **does not** re-run the `getWinner()` algorithm to check if the masternode declared in the block header (`block->masternode`) is the correct one. It only verifies:
    *   That the claimed winner is a *valid, verified* masternode.
    *   That the block's `mn_signature` was correctly signed by the claimed winner.
3.  **Exploitation:** A malicious generator can collude with any other verified masternode on the network.
    *   The generator creates a new block and simply ignores the true winner from `Masternode::getWinner()`.
    *   They insert the address of their colluding partner into the `block->masternode` field.
    *   The colluding masternode signs the block, providing a valid `mn_signature`.
    *   The generator includes the valid masternode reward transaction for their partner and broadcasts the block.

This block will be accepted by the network as valid. The validation logic is missing the crucial step of re-calculating the winner and comparing it to the winner claimed in the block. This allows a generator to play favorites, enabling collusion and undermining the fairness of the reward distribution.

## Attack Vector 2: Denial-of-Service (Chain Stalling) - VIABLE

The most significant attack a masternode (or group of masternodes) can perform is to halt the blockchain.

### How it Works

1.  **Masternode Signature Requirement:** After `UPDATE_5_NO_MASTERNODE`, if there are active masternodes on the network, any new block must include a valid `mn_signature`.
2.  **The Attack:** If a majority or all of the masternodes on the network collude to go offline (or simply stop broadcasting their signatures), they will no longer be considered "verified". Consequently, the `Masternode::getWinner()` function will find no eligible candidates.
3.  **Chain Halt:** When no winner is found, the block generation process in `web/mine.php` fails, and the block validation process in `Masternode::verifyBlock()` will reject any block that is missing a required masternode signature. This effectively halts the chain.

## Attack Vector 3: Transaction Censorship - NOT VIABLE

Masternodes **cannot** directly censor transactions. Their role is to validate and sign a block that has already been constructed by a block generator. They do not choose which transactions are included.

## Attack Vector 4: Chain Manipulation (Forks) - NOT VIABLE

A cartel of masternodes **cannot** create a valid alternate chain on their own. A block must still have a valid Proof-of-Work solution and a valid signature from the generator. The `mn_signature` is an additional check, not a replacement for the core security model.
