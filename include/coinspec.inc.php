<?php
$_config['chain_id'] = trim(@file_get_contents(dirname(__DIR__)."/chain_id"));
if(empty($_config['chain_id']) || !file_exists(__DIR__ . "/coinspec.".$_config['chain_id'].".php")) {
    $_config['chain_id'] = '00';
}
require_once __DIR__ . "/coinspec.".$_config['chain_id'].".php";
