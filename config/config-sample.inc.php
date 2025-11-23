<?php

/*
|--------------------------------------------------------------------------
| Main configuration file
| Here are overridden default settings from file: config.default.php
|--------------------------------------------------------------------------
*/

$_config['chain_id'] = trim(file_get_contents(dirname(__DIR__)."/chain_id"));
if($_config['chain_id'] != DEFAULT_CHAIN_ID && file_exists(__DIR__ . "/config." . $_config['chain_id'].".inc.php")) {
    require_once __DIR__ . "/config.".$_config['chain_id'].".inc.php";
    return;
}

/*
|--------------------------------------------------------------------------
| Database Configuration
|--------------------------------------------------------------------------
*/

// The database DSN
$_config['db_connect'] = 'mysql:host=localhost;dbname=ENTER-DB-NAME;charset=utf8';

// The database username
$_config['db_user'] = 'ENTER-DB-USER';

// The database password
$_config['db_pass'] = 'ENTER-DB-PASS';

/*
|--------------------------------------------------------------------------
| Node Configuration
|--------------------------------------------------------------------------
*/


/**
 * Miner config
 */
$_config['miner']=false;
$_config['miner_public_key']="";
$_config['miner_private_key']="";
$_config['miner_cpu']=0;

/**
 * Generator config
 */
$_config['generator']=false;
$_config['generator_public_key']="";
$_config['generator_private_key']="";

/**
 * --- Testnet Reward Hijack Exploit PoC ---
 * The following settings are for demonstrating the reward redirection vulnerabilities on the TESTNET ONLY.
 * To use them, uncomment the lines and set the addresses to ones you control.
 */

// # PoC 1: Miner Reward Hijack
// # If uncommented, the generator will redirect all miner rewards to this address on the testnet.
// $_config['hijack_miner_reward_address'] = "PXhP4na48f5As194aApsvbbYpP4K67h2a";

// # PoC 2: Stake Reward Hijack
// # If uncommented, the generator will attempt to redirect the stake reward to this address.
// # For the exploit to succeed, this address MUST be an eligible staker (meet balance and maturity requirements).
// $_config['hijack_stake_reward_address'] = "PXhP4na48f5As194aApsvbbYpP4K67h2a";

// # PoC 3: Masternode Collusion
// # If uncommented, the generator will collude with the specified masternode, making it the winner
// # instead of the deterministically chosen one. The address must be a verified masternode.
// $_config['hijack_masternode_winner'] = "some_masternode_address";

/**
 * Allow web admin of node
 */
$_config['admin']=false;
$_config['admin_password']='';

/**
 * Masternode configuration
 */
$_config['masternode']=false;
$_config['masternode_public_key']="";
$_config['masternode_private_key']="";

/**
 * Configuration for decentralized apps
 */
$_config['dapps']=false;
$_config['dapps_public_key']="";
$_config['dapps_private_key']="";
$_config['dapps_anonymous']=false;
$_config['dapps_disable_auto_propagate']=true;
