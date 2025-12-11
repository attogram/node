<?php
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../utils/scutil.php';

// --- Configuration ---
// IMPORTANT: Replace these placeholders with your actual data before running this script.

// 1. Connection and Account Details
$node_url = "http://127.0.0.1"; // The URL of your PHPCoin node's API.
$private_key = "your_private_key_here"; // The private key of the account that will deploy the contract and pay the fees.

// 2. Smart Contract Details
// A new, unique address for your smart contract. You can generate one using the wallet script.
$sc_address = "your_new_smart_contract_address_here";
// The path to the smart contract source file.
$source_file = __DIR__.'/../smart-contracts/NodeStats.php';

// 3. Deployment Parameters
// The NodeStats contract's deploy() method does not require any parameters.
$deploy_params = [];

// --- Script Execution ---

// Verify that placeholder values have been changed
if ($private_key === "your_private_key_here" || $sc_address === "your_new_smart_contract_address_here") {
    die("Please update the placeholder variables in this script before running.\n");
}

echo "PHPCoin Smart Contract Deployment Script: NodeStats\n";
echo "--------------------------------------------------\n";

// --- Step 1: Compile the Smart Contract ---
$output_dir = __DIR__.'/../build';
if (!is_dir($output_dir)) {
    mkdir($output_dir, 0777, true);
}
$output_file = $output_dir . '/NodeStats.phar';

echo "Compiling contract...\n";
echo " -> Source: $source_file\n";
echo " -> Output: $output_file\n";

// The compilation command from the PHPCoin documentation.
$compile_command = sprintf(
    'php %s/../utils/sc_compile.php %s %s %s',
    __DIR__,
    escapeshellarg($sc_address),
    escapeshellarg($source_file),
    escapeshellarg($output_file)
);

// Execute the compilation command
$compile_output = shell_exec($compile_command);
echo $compile_output;

if (!file_exists($output_file)) {
    die("Compilation failed. The output file was not created.\n");
}

echo "Compilation successful!\n\n";

// --- Step 2: Deploy the Compiled Contract ---
echo "Deploying contract to address: $sc_address\n";

try {
    // Generate the deployment transaction
    $tx = SCUtil::generateDeployTx($output_file, $private_key, $sc_address, 0, $deploy_params);

    // Send the transaction to the network via the node
    $tx_id = SCUtil::sendTx($node_url, $tx);

    if ($tx_id) {
        echo "Deployment transaction sent successfully!\n";
        echo " -> Transaction ID: $tx_id\n";
        echo " -> You can now use the `update_node_stats.php` script with the contract address: $sc_address\n";
    } else {
        echo "Deployment failed. The transaction was not accepted by the node.\n";
    }
} catch (Exception $e) {
    die("An error occurred during deployment: " . $e->getMessage() . "\n");
}

echo "\nDeployment process complete.\n";
