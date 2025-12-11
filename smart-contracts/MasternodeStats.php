<?php

class MasternodeStats extends SmartContractBase
{
    const SC_CLASS_NAME = "MasternodeStats";

    /**
     * @SmartContractMap
     * Stores a hybrid snapshot of masternode statistics.
     * The key is the masternode's address, and the value is a JSON string of its stats.
     */
    public SmartContractMap $masternodeStats;

    /**
     * @SmartContractDeploy
     * The deploy method is called once when the contract is deployed.
     */
    public function deploy()
    {
        // No initial state is needed for this contract.
    }

    /**
     * @SmartContractTransact
     * Performs a network-wide census of deterministic stats by querying the local
     * node's database. This updates the baseline stats for all verified masternodes.
     * This function can be called by anyone to refresh the census.
     */
    public function collect()
    {
        global $db;
        $verified_masternodes = $db->run("SELECT id, ip, height, win_height, collateral, verified FROM masternode WHERE verified = 1");

        if (is_array($verified_masternodes)) {
            foreach ($verified_masternodes as $node) {
                // This creates a baseline of deterministic, on-chain data.
                $stats = [
                    'ip' => $node['ip'],
                    'height' => (int)$node['height'],
                    'win_height' => (int)$node['win_height'],
                    'collateral' => $node['collateral'],
                    'verified' => (int)$node['verified'],
                    'last_collected_at' => (int)$this->height
                ];
                $this->masternodeStats[$node['id']] = json_encode($stats);
            }
        }
    }

    /**
     * @SmartContractTransact
     * Allows a masternode to report its own non-deterministic stats (e.g., software version).
     * The contract identifies the node by the transaction sender's address.
     * @param string $statsJson A JSON string of detailed, non-deterministic stats.
     */
    public function reportStats($statsJson)
    {
        $masternodeAddress = $this->src;

        // A masternode must be in the census before it can report detailed stats.
        $existingStatsJson = $this->masternodeStats[$masternodeAddress];
        if ($existingStatsJson === null) {
            $this->error("NODE_NOT_IN_CENSUS: Please run collect() first.");
        }

        // Decode the new stats provided by the node.
        $newStats = json_decode($statsJson, true);
        if ($newStats === null) {
            $this->error("INVALID_STATS_JSON: The provided stats string could not be decoded.");
        }

        // Merge the new, non-deterministic stats into the existing deterministic record.
        $existingStats = json_decode($existingStatsJson, true);
        $mergedStats = array_merge($existingStats, $newStats);

        // Add a timestamp for this specific report.
        $mergedStats['last_reported_at'] = (int)$this->height;

        // Save the updated, combined record.
        $this->masternodeStats[$masternodeAddress] = json_encode($mergedStats);
    }

    /**
     * @SmartContractView
     * Retrieves the last collected statistics for a specific masternode.
     * @param string $masternodeAddress The address of the masternode.
     * @return string|null The masternode's stats as a JSON string, or null if not found.
     */
    public function getStats($masternodeAddress)
    {
        return $this->masternodeStats[$masternodeAddress];
    }
}
