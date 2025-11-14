<?php

require_once dirname(dirname(__DIR__)) . '/include/init.inc.php';


global $_config;
$_config['enable_message_parsing']=false;
$nodeScore = round($_config['node_score'],2);

