<?php
require_once dirname(__DIR__).'/vendor/autoload.php';
require_once dirname(__DIR__).'/utils/scutil.php';

// --- Configuration ---
// IMPORTANT: Replace these placeholders with your actual data
$node_url = "http://127.0.0.1"; // URL of a reliable, primary PHPCoin node
$private_key = "your_private_key_here"; // The private key of the account that will pay the transaction fees
$sc_address = "your_node_stats_contract_address_here"; // The address of the deployed NodeStats contract

// --- Script Execution ---

// Verify that placeholder values have been changed
if ($private_key === "your_private_key_here" || $sc_address === "your_node_stats_contract_address_here") {
    die("Please update the placeholder variables in this script before running.\n");
}

// Function to fetch data from a node's API
function getNodeApiResponse($url, $query) {
    $full_url = rtrim($url, '/') . '/api.php?q=' . $query;
    // Set a timeout to prevent the script from hanging on unresponsive nodes
    $context = stream_context_create(['http' => ['timeout' => 5]]);
    $response = @file_get_contents($full_url, false, $context);
    if ($response === false) {
        // Return null instead of dying to handle offline nodes gracefully
        return null;
    }
    $data = json_decode($response, true);
    if (!isset($data['status']) || $data['status'] !== 'ok') {
        // Return null for API errors
        return null;
    }
    return $data['data'];
}

// 1. Get the list of all active masternodes from our primary node
echo "Fetching masternode list from $node_url...\n";
$masternodes = getNodeApiResponse($node_url, 'getMasternodes');

if (empty($masternodes)) {
    echo "Could not fetch masternode list or no masternodes found.\n";
    exit;
}

echo "Found " . count($masternodes) . " masternodes. Querying each for stats...\n";

// 2. Iterate through each masternode and update its stats
foreach ($masternodes as $masternode) {
    $mn_address = $masternode['id'];
    $mn_ip = $masternode['ip'];

    // Skip nodes with no IP address
    if (empty($mn_ip) || !filter_var($mn_ip, FILTER_VALIDATE_IP)) {
        echo "Skipping masternode $mn_address (invalid or missing IP).\n";
        continue;
    }

    $individual_node_url = "http://" . $mn_ip;
    echo "Processing masternode: $mn_address at $individual_node_url\n";

    // 3. Fetch the individual node's information
    $node_info = getNodeApiResponse($individual_node_url, 'nodeInfo');

    if ($node_info === null) {
        echo "  -> Failed to get stats. Node might be offline or firewalled.\n";
        continue; // Skip to the next masternode
    }

    // 4. Prepare the statistics payload from the collected data
    $stats = [
        'phpcoin_version' => $node_info['version'] ?? 'unknown',
        'block_height' => $node_info['height'] ?? 'unknown',
        'total_peers' => $node_info['peers'] ?? 'unknown',
        'last_updated' => time(),
    ];
    $statsJson = json_encode($stats);

    // 5. Generate and send the transaction to the smart contract
    try {
        $params = [$mn_address, $statsJson];
        // Use the primary node to send the transaction
        $tx = SCUtil::generateScExecTx($private_key, $sc_address, "updateStats", 0, $params);
        $tx_id = SCUtil::sendTx($node_url, $tx);

        if ($tx_id) {
            echo "  -> Stats update transaction sent: $tx_id\n";
        } else {
            echo "  -> Failed to send stats update transaction via $node_url.\n";
        }
    } catch (Exception $e) {
        echo "  -> Error sending transaction for $mn_address: " . $e->getMessage() . "\n";
    }

    // Optional: Add a small delay
    sleep(1);
}

echo "Finished updating node stats.\n";
