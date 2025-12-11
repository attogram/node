<?php

class NodeStats extends SmartContractBase
{
    const SC_CLASS_NAME = "NodeStats";

    /**
     * @SmartContractMap
     * A key-value store for masternode statistics.
     * The key is the masternode address, and the value is a JSON string of stats.
     */
    public SmartContractMap $nodeStats;

    /**
     * @SmartContractDeploy
     * This method is called once when the contract is deployed.
     * For this contract, it does nothing.
     */
    public function deploy()
    {
        // No initial state to set.
    }

    /**
     * @SmartContractTransact
     * Updates the statistics for a given masternode.
     * Anyone can call this method.
     * @param string $nodeAddress The address of the masternode.
     * @param string $statsJson A JSON string containing the node's statistics.
     */
    public function updateStats($nodeAddress, $statsJson)
    {
        if (empty($nodeAddress) || strlen($nodeAddress) < 26 || strlen($nodeAddress) > 35) {
            $this->error("INVALID_NODE_ADDRESS");
        }
        $this->nodeStats[$nodeAddress] = $statsJson;
    }

    /**
     * @SmartContractView
     * Retrieves the statistics for a specific masternode.
     * @param string $nodeAddress The address of the masternode.
     * @return string|null The node's statistics as a JSON string, or null if not found.
     */
    public function getStats($nodeAddress)
    {
        return $this->nodeStats[$nodeAddress];
    }
}
