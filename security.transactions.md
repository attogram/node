# PHPCoin Security Analysis: Transaction Integrity

## Can a bad node change transactions in a block?

No. A malicious node cannot alter, add, remove, or reorder transactions within a block that has already been created. The integrity of transactions is protected by a two-layer security model based on digital signatures.

### 1. Transaction-Level Security

Every transaction is individually signed by the sender. The signature is created over the core data of the transaction, including:
*   Amount (`val`)
*   Fee (`fee`)
*   Destination Address (`dst`)
*   Message (`msg`)
*   Transaction Type (`type`)
*   Sender's Public Key (`publicKey`)
*   Date (`date`)

This is handled by the `Transaction::getSignatureBase()` method. If a malicious node were to intercept a transaction and alter any of these fields (e.g., change the destination address), the signature would no longer be valid. When other nodes in the network receive the tampered transaction, they will fail to verify the signature and will reject it.

### 2. Block-Level Security

The entire set of transactions included in a block is also cryptographically secured by the block generator's signature.

The `Block::getSignatureBase()` method creates a unique signature base for the entire block. A crucial part of this base is a JSON-encoded string of all transactions in the block's `data` array. To ensure this is deterministic, the transactions are first sorted by their ID (`ksort($data)`).

This means a malicious node cannot:
*   **Alter a transaction:** Changing even a single character in any transaction would change the overall hash of the `data` array and invalidate the block's signature.
*   **Add or remove a transaction:** Modifying the set of transactions would also invalidate the block's signature.
*   **Reorder transactions:** The deterministic sorting ensures that any reordering would be detected.

### Transaction Censorship

While a node cannot alter the contents of a finalized block, a block generator (miner/staker) **can** exercise a form of censorship by choosing which transactions from the mempool to include in a new block they are creating.

A malicious generator could choose to:
*   **Exclude specific transactions:** They could ignore valid transactions from certain addresses.
*   **Prioritize their own transactions:** They could include only their own transactions or those that pay a high fee.

This is a fundamental challenge in most blockchain systems. The primary defense is economic: if a generator consistently ignores valid, fee-paying transactions, another, more honest generator will eventually include them in a future block to collect the fees.
