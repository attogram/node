<?php
if(php_sapi_name() !== 'cli') exit;
const DEFAULT_CHAIN_ID = "01";
const MINER_VERSION = "1.5";
if(Phar::running()) {
	require_once 'vendor/autoload.php';
} else {
	require_once dirname(__DIR__).'/vendor/autoload.php';
}

function usage() {
    echo "PHPCoin Miner Version ".MINER_VERSION.PHP_EOL;
    echo "Usage: php miner.php [options]".PHP_EOL;
    echo "Options:".PHP_EOL;
    echo "  -n, --node=<url>      Node URL to connect to".PHP_EOL;
    echo "  -a, --address=<addr>  Address to mine for".PHP_EOL;
    echo "  -c, --cpu=<percent>   CPU usage percentage (default: 50)".PHP_EOL;
    echo "  -t, --threads=<num>   Number of mining threads (default: 1)".PHP_EOL;
    echo "  -o, --output=<fmt>    Output format (default|fancy). Default is 'fancy'".PHP_EOL;
    echo "  -h, --help            Show this help message and exit".PHP_EOL;
    echo PHP_EOL;
    echo "A miner.conf file can be used for default values.".PHP_EOL;
    exit;
}

$short_opts = "n:a:c:t:o:h";
$long_opts = ["node:", "address:", "cpu:", "threads:", "output:", "help"];
$options = getopt($short_opts, $long_opts);

if ($argc == 1 || isset($options['h']) || isset($options['help'])) {
    usage();
}

$node = null;
$address = null;
$cpu = 50;
$threads = 1;
$output_format = 'fancy';

if(file_exists(getcwd()."/miner.conf")) {
	$minerConf = parse_ini_file(getcwd()."/miner.conf");
	$node = @$minerConf['node'];
	$address = @$minerConf['address'];
	$cpu = @$minerConf['cpu'];
    $threads = @$minerConf['threads'];
    $output_format = @$minerConf['output_format'];
}

// CLI options override config file
if (isset($options['n'])) $node = $options['n'];
if (isset($options['node'])) $node = $options['node'];
if (isset($options['a'])) $address = $options['a'];
if (isset($options['address'])) $address = $options['address'];
if (isset($options['c'])) $cpu = (int)$options['c'];
if (isset($options['cpu'])) $cpu = (int)$options['cpu'];
if (isset($options['t'])) $threads = (int)$options['t'];
if (isset($options['threads'])) $threads = (int)$options['threads'];
if (isset($options['o'])) $output_format = $options['o'];
if (isset($options['output'])) $output_format = $options['output'];

if(empty($threads)) $threads = 1;
if(empty($cpu)) $cpu = 50;
if(empty($output_format)) $output_format = 'fancy';
if($cpu > 100) $cpu = 100;


echo "PHPCoin Miner Version ".MINER_VERSION.PHP_EOL;
echo "Mining server:  ".$node.PHP_EOL;
echo "Mining address: ".$address.PHP_EOL;
echo "CPU:            ".$cpu.PHP_EOL;
echo "Threads:        ".$threads.PHP_EOL;

if(empty($node)) {
    echo "Error: Node not defined.".PHP_EOL.PHP_EOL;
    usage();
}
if(empty($address)) {
	echo "Error: Address not defined.".PHP_EOL.PHP_EOL;
    usage();
}

$res = url_get($node . "/api.php?q=getPublicKey&address=".$address);
if(empty($res)) {
	die("No response from node".PHP_EOL);
}
$res = json_decode($res, true);
if(empty($res)) {
	die("Invalid response from node".PHP_EOL);
}
if(!($res['status']=="ok" && !empty($res['data']))) {
	die("Invalid response from node: ".json_encode($res).PHP_EOL);
}

echo "Network:        ".$res['network'].PHP_EOL;

$_config['enable_logging'] = true;
$_config['log_verbosity']=0;
$_config['log_file']="/dev/null";
$_config['chain_id'] = trim(file_exists(dirname(__DIR__)."/chain_id"));

define("ROOT", __DIR__);

function startMiner($address,$node, $forked) {
    global $cpu, $output_format;
    $miner = new Miner($address, $node, $forked);
    $miner->outputFormat = $output_format;
    $miner->cpu = $cpu;
    $miner->start();
}

if($threads == 1) {
    startMiner($address,$node, false);
} else {
    $forker = new Forker();
    for($i=1; $i<=$threads; $i++) {
        $forker->fork(function() use ($address,$node) {
            startMiner($address,$node, true);
        });
    }
    $forker->exec();
}
