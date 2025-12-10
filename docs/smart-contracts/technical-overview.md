[PHPCoin Docs](../) > [Smart Contracts](./) > Technical Overview

# Smart Contract Technical Overview

This document provides a technical overview of how PHPCoin smart contracts are deployed, executed, and viewed, with references to the underlying PHPcoin node codebase.

## Deployment

Deploying a smart contract is a multi-step process that involves compiling the contract, generating a deployment transaction, and broadcasting it to the network.

### 1. Compilation

The first step is to compile the smart contract's PHP source code into a `.phar` file. This is done using the `utils/sc_compile.php` script, which is invoked by the `SmartContract::compile` function in `include/class/SmartContract.php`.

The compilation process packages the contract's source code and its dependencies into a single, executable archive. This archive is what will be stored on the blockchain.

### 2. Transaction Generation

Once the contract is compiled, a deployment transaction is generated using the `SCUtil::generateDeployTx` function in `utils/scutil.php`. This function takes the compiled `.phar` file, the contract's address, and other parameters, and constructs a transaction of type `TX_TYPE_SC_CREATE`.

The transaction's `data` field contains the base64-encoded `.phar` file, the contract's interface, and any deployment parameters. The transaction is then signed with the private key of the deploying account.

### 3. Node-side Verification and Processing

When a node receives a `TX_TYPE_SC_CREATE` transaction, it performs a series of checks in the `SmartContract::checkCreateSmartContractTransaction` function in `include/class/SmartContract.php`. These checks include:

*   Verifying the transaction's signature.
*   Ensuring the contract address is not already in use.
*   Validating the transaction fee.

If the transaction is valid, it is passed to the `SmartContractEngine` for processing. The `SmartContractEngine` executes the contract's `@SmartContractDeploy` method, which initializes the contract's state. The contract's code and initial state are then written to the blockchain.

## Execution

Executing a smart contract involves creating a transaction that calls a specific method on the contract. This process is used for methods annotated with `@SmartContractTransact`, which are designed to modify the contract's state.

### 1. Transaction Generation

To execute a contract method, a transaction of type `TX_TYPE_SC_EXEC` is generated using the `SCUtil::generateScExecTx` function in `utils/scutil.php`. This function takes the private key of the calling account, the contract's address, the name of the method to execute, and any parameters the method requires.

### 2. Node-side Verification and Processing

When a node receives a `TX_TYPE_SC_EXEC` transaction, it performs a series of checks in the `SmartContract::checkExecSmartContractTransaction` function in `include/class/SmartContract.php`. These checks include:

*   Verifying that the smart contract exists at the specified address.
*   Validating the transaction fee.

If the transaction is valid, it is passed to the `SmartContract::processSmartContractTx` function. This function sorts all transactions for the contract and then calls the `SmartContractEngine::process` function, which is responsible for executing the contract's code.

The `SmartContractEngine` loads the contract's code and its current state from the blockchain, then executes the specified method with the provided parameters. Any changes made to the contract's state during execution are written back to the blockchain.

## Viewing

Viewing a smart contract's state is a read-only process that does not require a transaction. This is used for methods annotated with `@SmartContractView`.

### 1. API Request

To view a contract's state, a request is made to the node's API, specifically to the `getSmartContractView` endpoint. This request is handled by the `Api::getSmartContractView` function in `include/class/Api.php`.

The `SCUtil::executeView` function in `utils/scutil.php` provides a convenient way to make this API request. It takes the contract's address, the name of the view method, and any parameters the method requires.

### 2. Node-side Processing

The `Api::getSmartContractView` function calls the `SmartContractEngine::view` function, which is responsible for executing the view method.

The `SmartContractEngine` loads the contract's code and its current state from the blockchain, then executes the specified view method with the provided parameters. Since this is a read-only operation, no state changes are written back to the blockchain. The result of the view method is then returned to the client.
