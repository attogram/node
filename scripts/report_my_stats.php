<?php
require_once dirname(__DIR__).'/vendor/autoload.php';
require_once dirname(__DIR__).'/utils/scutil.php';

// --- Configuration ---
// IMPORTANT: This script should be run on a masternode.
// It requires the masternode's private key to sign the transaction.

// 1. Connection and Contract Details
$node_url = "http://127.0.0.1"; // URL of a reliable, primary PHPCoin node to send the transaction through.
$sc_address = "your_masternode_stats_contract_address_here"; // The address of the deployed MasternodeStats contract.

// 2. Masternode Identity
// The private key of THIS masternode. It will be used to sign the transaction,
// identifying this node to the smart contract via `$this->src`.
$masternode_private_key = "your_masternode_private_key_here";

// --- Script Execution ---

// Verify that placeholder values have been changed
if ($masternode_private_key === "your_masternode_private_key_here" || $sc_address === "your_masternode_stats_contract_address_here") {
    die("Please update the placeholder variables in this script before running.\n");
}

echo "Masternode Self-Reporting Script\n";
echo "--------------------------------\n";

// --- Step 1: Gather Non-Deterministic Stats ---
// This is where you can collect any information unique to this node.
echo "Gathering local node stats...\n";
$local_stats = [
    'php_version' => PHP_VERSION,
    'os_version' => php_uname(),
    // You could add more complex stats here, e.g., disk space, memory usage, etc.
    // 'disk_free_space' => disk_free_space('/'),
];
$statsJson = json_encode($local_stats);
echo " -> Stats payload: $statsJson\n";

// --- Step 2: Send the Report to the Smart Contract ---
echo "Sending stats report to contract: $sc_address\n";

try {
    // The `reportStats` method takes one parameter: the JSON string of stats.
    $params = [$statsJson];

    // Generate the transaction. The private key signs it, so the contract knows who sent it.
    $tx = SCUtil::generateScExecTx($masternode_private_key, $sc_address, "reportStats", 0, $params);

    // Send the transaction to the network via a reliable node.
    $tx_id = SCUtil::sendTx($node_url, $tx);

    if ($tx_id) {
        echo "Report transaction sent successfully!\n";
        echo " -> Transaction ID: $tx_id\n";
    } else {
        echo "Failed to send report. The transaction was not accepted by the node.\n";
    }
} catch (Exception $e) {
    die("An error occurred while sending the report: " . $e->getMessage() . "\n");
}

echo "\nReporting process complete.\n";
