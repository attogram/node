<?php
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../utils/scutil.php';

// --- Configuration ---
// IMPORTANT: Replace these placeholders with your actual data before running this script.

// 1. Connection and Account Details
$node_url = "http://127.0.0.1"; // The URL of your PHPCoin node's API.
$private_key = "your_private_key_here"; // The private key of the account that will deploy the contract and pay the fees.

// 2. Smart Contract Details
$sc_address = "your_new_smart_contract_address_here"; // A new, unique address for your smart contract.
$source_file = __DIR__.'/../smart-contracts/MasternodeStats.php';

// --- Script Execution ---

// Verify that placeholder values have been changed
if ($private_key === "your_private_key_here" || $sc_address === "your_new_smart_contract_address_here") {
    die("Please update the placeholder variables in this script before running.\n");
}

echo "PHPCoin Smart Contract Deployment Script: MasternodeStats\n";
echo "--------------------------------------------------------\n";

// --- Step 1: Compile the Smart Contract ---
$output_dir = __DIR__.'/../build';
if (!is_dir($output_dir)) {
    mkdir($output_dir, 0777, true);
}
$output_file = $output_dir . '/MasternodeStats.phar';

echo "Compiling contract...\n";
$compile_command = sprintf(
    'php %s/../utils/sc_compile.php %s %s %s',
    __DIR__,
    escapeshellarg($sc_address),
    escapeshellarg($source_file),
    escapeshellarg($output_file)
);

$compile_output = shell_exec($compile_command);
echo $compile_output;

if (!file_exists($output_file)) {
    die("Compilation failed. The output file was not created.\n");
}

echo "Compilation successful!\n\n";

// --- Step 2: Deploy the Compiled Contract ---
echo "Deploying contract to address: $sc_address\n";

try {
    // The MasternodeStats contract's deploy() method has no parameters.
    $deploy_params = [];
    $tx = SCUtil::generateDeployTx($output_file, $private_key, $sc_address, 0, $deploy_params);
    $tx_id = SCUtil::sendTx($node_url, $tx);

    if ($tx_id) {
        echo "Deployment transaction sent successfully!\n";
        echo " -> Transaction ID: $tx_id\n";
        echo " -> Contract Address: $sc_address\n\n";

        echo "--- USAGE INSTRUCTIONS ---\n";
        echo "The MasternodeStats system is now deployed. It works in two parts:\n\n";
        echo "Part 1: Network-Wide Census (Deterministic Stats)\n";
        echo " -> Anyone can call the 'collect()' method on the contract to refresh the on-chain list of verified masternodes.\n";
        echo " -> This provides a baseline of deterministic data (IP, collateral, etc.).\n";
        echo " -> Example command to call collect (replace with the appropriate private key):\n";
        echo "    php scripts/scutil.php execute your_private_key_here $sc_address collect '[]'\n\n";

        echo "Part 2: Individual Node Reporting (Non-Deterministic Stats)\n";
        echo " -> Each masternode operator should run the 'report_my_stats.php' script.\n";
        echo " -> This script gathers unique local stats (like PHP version) and sends them to the contract.\n";
        echo " -> This should be set up as a cron job on each masternode to keep stats fresh.\n";
        echo " -> Operators must edit 'scripts/report_my_stats.php' to add their masternode's private key.\n";

    } else {
        echo "Deployment failed. The transaction was not accepted by the node.\n";
    }
} catch (Exception $e) {
    die("An error occurred during deployment: " . $e->getMessage() . "\n");
}

echo "\nDeployment process complete.\n";
