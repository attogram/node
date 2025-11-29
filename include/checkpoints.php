<?php

$chain_id = trim(@file_get_contents(dirname(__DIR__)."/chain_id"));
if(empty($chain_id) || !file_exists(__DIR__ . "/checkpoints.".$chain_id.".php")) {
    $chain_id = '00';
}
require_once __DIR__ . "/checkpoints.".$chain_id.".php";
