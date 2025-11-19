<?php
if(php_sapi_name() !== 'cli') exit;

$_config['enable_logging'] = true;
$_config['log_verbosity']=0;
$_config['log_file']="/dev/null";
define("ROOT", __DIR__);

const NETWORK = "mainnet";
const CHAIN_ID = "00";
const COIN_PORT = "";
const VERSION = "1.6.8";
const BUILD_VERSION = 389;
const MIN_VERSION = "1.6.6";
const DEVELOPMENT = false;
const XDEBUG = "";
const XDEBUG_CLI = "";
const GIT_BRANCH = "main";
const MIN_MINER_VERSION = "1.3";

const COIN = "phpcoin";
const COIN_NAME="PHPCoin";
const COIN_SYMBOL="PHP";
const CHAIN_PREFIX = "38";
const COIN_DECIMALS = 8;
const BASE_REWARD = 100;
const GENESIS_REWARD = 103200000;
const BLOCKCHAIN_CHECKPOINT = 1;

const BLOCK_TIME = 60;
const BLOCK_TARGET_MUL = 1000;
const BLOCK_START_DIFFICULTY = "60000";

const TX_FEE = 0;
const TX_TYPE_REWARD = 0;
const TX_TYPE_SEND = 1;
const TX_TYPE_MN_CREATE = 2;
const TX_TYPE_MN_REMOVE = 3;
const TX_TYPE_FEE = 4;
const TX_TYPE_SC_CREATE = 5;
const TX_TYPE_SC_EXEC = 6;
const TX_TYPE_SC_SEND = 7;
const TX_TYPE_BURN = 8;
const TX_TYPE_SYSTEM = 9;

const HASHING_ALGO = PASSWORD_ARGON2I;
const HASHING_OPTIONS = ['memory_cost' => 2048, "time_cost" => 2, "threads" => 1];
const REMOTE_PEERS_LIST_URL = "https://main1.phpcoin.net/peers.php";

const MIN_NODE_SCORE = 80;

const FEATURE_MN = true;
const MN_MIN_RUN_BLOCKS = 1440*30;
const MN_START_HEIGHT = 20001;

const FEE_START_HEIGHT = PHP_INT_MAX;
const FEE_DIVIDER = 100;

# Smart contracts
const FEATURE_SMART_CONTRACTS = true;
const SC_START_HEIGHT = 1035400;
const TX_TYPE_BURN_START_HEIGHT = 0;
const STAKING_START_HEIGHT = 20001;

const SC_MAX_EXEC_TIME = 30;
const SC_MEMORY_LIMIT = "256M";

const GIT_URL = "https://github.com/phpcoinn/node";
const UPDATE_1_BLOCK_ZERO_TIME = 0;
const UPDATE_2_BLOCK_CHECK_IMPROVED = 0;
const UPDATE_3_ARGON_HARD = 0;
const UPDATE_4_NO_POOL_MINING = 0;
const UPDATE_5_NO_MASTERNODE = 0;
const UPDATE_6_CHAIN_ID = 0;
const UPDATE_7_MINER_CHAIN_ID = 0;
const UPDATE_8_FIX_CHECK_BURN_TX_DST_NULL = [0, 0];
const UPDATE_9_ADD_MN_COLLATERAL_TO_SIGNATURE = 0;
const UPDATE_10_ZERO_TX_NOT_ALLOWED = 27000;
const UPDATE_11_STAKING_MATURITY_REDUCE = 290000;
const UPDATE_12_STAKING_DYNAMIC_THRESHOLD = 290000;
const UPDATE_13_LIMIT_GENERATOR=536500;
const UPDATE_14_EXTENDED_SC_HASH=0;
const UPDATE_15_EXTENDED_SC_HASH_V2=0;
const UPDATE_16_SC_TXS_SORT=0;
const UPDATE_17_DEV_MINER_START=PHP_INT_MAX;

const DEV_PUBLIC_KEY = "PZ8Tyr4Nx8MHsRAGMpZmZ6TWY63dXWSCyao5hHHJd9axKhC1c5emTgT4hT7k7EvXiZrjTJSGEPmz9K1swEDQi8j14vCRwUisMsvHr4P5kirrDawM3NJiknWR";

const MAIN_DAPPS_ID = "PeC85pqFgRxmevonG6diUwT4AfF7YUPSm3";
const TOTAL_INITIAL_SUPPLY = 103200000;

const STOP_CHAIN_HEIGHT = PHP_INT_MAX;
const DELETE_CHAIN_HEIGHT = PHP_INT_MAX;

const MN_CREATE_IGNORE_HEIGHT = [30234];
const MN_COLD_START_HEIGHT = 290000;

const IGNORE_SC_HASH_HEIGHT = [];
const BLACKLISTED_SMART_CONTRACTS = [];

const DEV_REWARD_ADDRESS = "PdEvtfZwNsbddKLCZQcjTgjpdcznS1w3pG";


function url_get($url,$timeout = 30) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	if(DEVELOPMENT) {
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, 0);
	} else {
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, 1);
	}
	$result = curl_exec($ch);
	if($result === false) {
		$err = curl_error($ch);
		//_log("Curl error: url=$url error=$err", 5);
	}
	curl_close ($ch);
	return $result;
}

function url_post($url, $postdata, $timeout=30) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata );
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	if(DEVELOPMENT) {
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, 0);
	} else {
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, 1);
	}
	$result = curl_exec($ch);
	if($result === false) {
		$err = curl_error($ch);
		//_log("Curl error: url=$url error=$err", 5);
	}
	curl_close ($ch);
	return $result;
}

function gmp_hexdec($n) {
	$gmp = gmp_init(0);
	$mult = gmp_init(1);
	for ($i=strlen($n)-1;$i>=0;$i--,$mult=gmp_mul($mult, 16)) {
		$gmp = gmp_add($gmp, gmp_mul($mult, hexdec($n[$i])));
	}
	return $gmp;
}

class Forker {

    private $parent;
    private $listener;
    private $childs = [];

    static function instance() {
        return new Forker();
    }

    function run(Closure $closure, ...$args) {
        $this->parent = [$closure, $args];
        return $this;
    }

    function fork(Closure $child, ...$args) {
        $this->childs[]=[$child, $args];
        return $this;
    }

    function exec() {
        define("FORKED_PROCESS", getmypid());
        if($this->parent) {
            $this->parent[0]->call($this, ...$this->parent[1]);
        }
        if(count($this->childs)==0) {
            return;
        }
        $sockets = [];
        foreach ($this->childs as $i=>$child) {
            $socket = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
            $pid = pcntl_fork();
            if ($pid == -1) {
                return;
            } else if (!$pid) {
                register_shutdown_function(function(){
                    posix_kill(getmypid(), SIGKILL);
                });
                fclose($socket[0]);
                $res = $child[0]->call($this, ...$child[1]);
                fwrite($socket[1], json_encode($res));
                fclose($socket[1]);
                exit;
            } else {
                fclose($socket[1]);
                $sockets[$i] = $socket;
            }
        }
        while (pcntl_waitpid(0, $status) != -1) ;
        foreach($sockets as $socket) {
            $output = stream_get_contents($socket[0]);
            fclose($socket[0]);
            $this->send($output);
        }
    }

    function execNoWait() {
        if($this->parent) {
            $this->parent[0]->call($this, ...$this->parent[1]);
        }
        if(count($this->childs)==0) {
            return;
        }
        foreach ($this->childs as $i=>$child) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                return;
            } else if (!$pid) {
                if (posix_setsid() == -1) {
                    exit();
                }
                register_shutdown_function(function(){
                    posix_kill(getmypid(), SIGKILL);
                });
                ob_start();
                $child[0]->call($this, ...$child[1]);
                ob_end_clean();
                exit;
            }
        }
    }

    function send($data) {
        if($this->listener) {
            $this->listener->call($this, json_decode($data, true));
        }
    }

    function on(Closure $listener) {
        $this->listener = $listener;
        return $this;
    }

}

class Peer
{
	static function validateIp($ip) {
		if(!DEVELOPMENT) {
			$ip = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_IPV4);
		}
		return $ip;
	}
}

class Block
{
    public $generator;
    public $miner;
    public $height;
    public $date;
    public $nonce;
    public $data;
    public $difficulty;
	public $version;
	public $argon;
	public $prevBlockId;

	public function __construct($generator, $miner, $height, $date, $nonce, $data, $difficulty, $version, $argon, $prevBlockId)
	{
		$this->generator = $generator;
		$this->miner = $miner;
		$this->height = $height;
		$this->date = $date;
		$this->nonce = $nonce;
		$this->data = $data;
		$this->difficulty = $difficulty;
		$this->version = $version;
		$this->argon = $argon;
		$this->prevBlockId = $prevBlockId;
	}

	function calculateHit() {
		$base = $this->miner . "-" . $this->nonce . "-" . $this->height . "-" . $this->difficulty;
		$hash = hash("sha256", $base);
		$hash = hash("sha256", $hash);
		$hashPart = substr($hash, 0, 8);
		$value = gmp_hexdec($hashPart);
		$hit = gmp_div(gmp_mul(gmp_hexdec("ffffffff"), BLOCK_TARGET_MUL) , $value);
		return $hit;
	}

	function calculateTarget($elapsed) {
		if($elapsed == 0) {
			return 0;
		}
		$target = gmp_div(gmp_mul($this->difficulty , BLOCK_TIME), $elapsed);
		return $target;
	}

    function calculateNonce($prev_block_date, $elapsed, $chain_id = CHAIN_ID) {
	$nonceBase = "{$chain_id}{$this->miner}-{$prev_block_date}-{$elapsed}-{$this->argon}";
	    $calcNonce = hash("sha256", $nonceBase);
	    return $calcNonce;
    }

	function calculateArgonHash($prev_block_date, $elapsed) {
		$base = "{$prev_block_date}-{$elapsed}";
		$options = self::hashingOptions($this->height);
		if($this->height < UPDATE_3_ARGON_HARD) {
			$options['salt']=substr($this->miner, 0, 16);
		}
		$argon = @password_hash(
			$base,
			HASHING_ALGO,
			$options
		);
		return $argon;
	}

	static function versionCode($height=null) {
		if($height == null) {
			$height = self::getHeight();
		}
        if(CHAIN_ID == "01") {
            if ($height < UPDATE_1_BLOCK_ZERO_TIME) {
                return "010000";
            } else if ($height >= UPDATE_1_BLOCK_ZERO_TIME && $height < UPDATE_2_BLOCK_CHECK_IMPROVED) {
                return "010001";
            } else if ($height >= UPDATE_2_BLOCK_CHECK_IMPROVED && $height < UPDATE_3_ARGON_HARD) {
                return "010002";
            } else if ($height >= UPDATE_3_ARGON_HARD && $height < UPDATE_6_CHAIN_ID) {
                return "010003";
            } else {
                return CHAIN_ID . "0004";
            }
        } else {
            return CHAIN_ID . "0000";
        }
	}

	static function hashingOptions($height=null) {
		if($height == null) {
			$height = self::getHeight();
		}
		if($height < UPDATE_3_ARGON_HARD) {
			return ['memory_cost' => 2048, "time_cost" => 2, "threads" => 1];
		} else {
			return ['memory_cost' => 32768, "time_cost" => 2, "threads" => 1];
		}
	}

	static function getHeight() {
		return 1;
	}
}

class Miner {


	public $address;
	public $private_key;
	public $node;
	public $miningStat;
	public $cnt = 0;
	public $block_cnt = 0;
	public $cpu = 25;
    public $minerid;
    private $forked;

	private $running = true;

    private $hashing_time = 0;
    private $hashing_cnt = 0;
    private $speed;
    private $sleep_time;
    private $attempt;

    private $miningNodes = [];

	function __construct($address, $node, $forked=false)
	{
		$this->address = $address;
		$this->node = $node;
        $this->minerid = time() . uniqid();
        $this->forked = $forked;
	}

	function getMiningInfo() {
        $info = $this->getMiningInfoFromNode($this->node);
        if($info === false) {
            if(is_array($this->miningNodes) && count($this->miningNodes)>0) {
                foreach ($this->miningNodes as $node) {
                    $info = $this->getMiningInfoFromNode($node);
                    if($info !== false) {
                        return $info;
                    }
                }
            }
            return false;
        } else {
            return $info;
        }
	}

    function getMiningInfoFromNode($node) {
        $url = $node."/mine.php?q=info";
        //_log("Getting info from url ". $url, 3);
        $info = url_get($url);
        if(!$info) {
            //_log("Error contacting peer");
            return false;
        }
        //_log("Received mining info: ".$info, 3);
        $info = json_decode($info, true);
        if ($info['status'] != "ok") {
            //_log("Wrong status for node: ".json_encode($info));
            return false;
        }
        return $info;
    }

    function getMiningNodes() {
        //_log("Get mining nodes from ".$this->node, 3);
        $url = $this->node."/mine.php?q=getMiningNodes";
        $info = url_get($url);
        if(!$info) {
            return;
        }
        $info = json_decode($info, true);
        if ($info['status'] != "ok") {
            return;
        }
        $this->miningNodes = $info['data'];
        //_log("Received ".count($this->miningNodes). " mining nodes", 3);
    }

    function sendStat($hashes, $height, $interval) {
        $postData = http_build_query([
            "address"=>$this->address,
            "minerid"=>$this->minerid,
            "cpu"=>$this->cpu,
            "hashes"=>$hashes,
            "height"=>$height,
            "interval"=>$interval,
            "miner_type"=>"cli",
            'minerInfo'=>'phpcoin-miner cli ' . VERSION,
            "version"=>VERSION
        ]);
        $res = url_post($this->node . "/mine.php?q=submitStat&", $postData);
    }

    function measureSpeed($t1, $th) {
        $t2 = microtime(true);
        $this->hashing_cnt++;
        $this->hashing_time = $this->hashing_time + ($t2-$th);

        $diff = $t2 - $t1;
        $this->speed = round($this->attempt / $diff,2);

        $calc_cnt = round($this->speed * 60);

        if($this->hashing_cnt % $calc_cnt == 0) {
            $this->sleep_time = $this->cpu == 0 ? INF : round((($this->hashing_time/$this->hashing_cnt)*1000)*(100-$this->cpu)/$this->cpu);
            if($this->sleep_time < 0) {
                $this->sleep_time = 0;
            }
        }
    }

	function start() {
        global $argv;
        $this->miningStat = [
			'started'=>time(),
			'hashes'=>0,
			'submits'=>0,
			'accepted'=>0,
			'rejected'=>0,
			'dropped'=>0,
		];
        $start_time = time();
        $prev_hashes = null;
		$this->sleep_time=(100-$this->cpu)*5;

        $this->getMiningNodes();

		while($this->running) {
			$this->cnt++;
////			_log("Mining cnt: ".$this->cnt);

			$info = $this->getMiningInfo();
			if($info === false) {
				//_log("Can not get mining info", 0);
				sleep(3);
				continue;
			}

			if(!isset($info['data']['generator'])) {
				//_log("Miner node does not send generator address");
				sleep(3);
				continue;
			}

			if(!isset($info['data']['ip'])) {
				//_log("Miner node does not send ip address ",json_encode($info));
				sleep(3);
				continue;
			}

			$ip = $info['data']['ip'];
			if(!Peer::validateIp($ip)) {
				//_log("Miner does not have valid ip address: $ip");
				sleep(3);
				continue;
			}

			$height = $info['data']['height']+1;
			$block_date = $info['data']['date'];
			$difficulty = $info['data']['difficulty'];
			$reward = $info['data']['reward'];
			$data = [];
			$nodeTime = $info['data']['time'];
			$prev_block_id = $info['data']['block'];
            $chain_id=$info['data']['chain_id'];
			$blockFound = false;


			$now = time();
			$offset = $nodeTime - $now;

			$this->attempt = 0;

			$bl = new Block(null, $this->address, $height, null, null, $data, $difficulty, Block::versionCode($height), null, $prev_block_id);

			$t1 = microtime(true);
			$prev_elapsed = null;
			while (!$blockFound) {
				$this->attempt++;
                if($this->sleep_time == INF) {
                    $this->running = false;
                    break;
                }
		        usleep($this->sleep_time * 1000);

				$now = time();
				$elapsed = $now - $offset - $block_date;
				$new_block_date = $block_date + $elapsed;
				//_log("Time=now=$now nodeTime=$nodeTime offset=$offset elapsed=$elapsed",4);
				$th = microtime(true);
				$bl->argon = $bl->calculateArgonHash($block_date, $elapsed);
				$bl->nonce=$bl->calculateNonce($block_date, $elapsed, $chain_id);
				$bl->date = $block_date;
				$hit = $bl->calculateHit();
				$target = $bl->calculateTarget($elapsed);
				$blockFound = ($hit > 0 && $target > 0 && $hit > $target);

                $this->measureSpeed($t1, $th);

				$s = "PID=".getmypid()." Mining attempt={$this->attempt} height=$height difficulty=$difficulty elapsed=$elapsed hit=$hit target=$target speed={$this->speed} submits=".
                    $this->miningStat['submits']." accepted=".$this->miningStat['accepted']. " rejected=".$this->miningStat['rejected']. " dropped=".$this->miningStat['dropped'];
                if(!$this->forked && !in_array("--flat-log", $argv)){
                    echo "$s \r";
                } else {
                    echo $s. PHP_EOL;
                }
				$this->miningStat['hashes']++;
				if($prev_elapsed != $elapsed && $elapsed % 10 == 0) {
					$prev_elapsed = $elapsed;
					$info = $this->getMiningInfo();
					if($info!==false) {
						//_log("Checking new block from server ".$info['data']['block']. " with our block $prev_block_id", 4);
						if($info['data']['block']!= $prev_block_id) {
							//_log("New block received", 2);
							$this->miningStat['dropped']++;
							break;
						}
					}
				}
                $send_interval = 60;
                $t=time();
                $elapsed_send = $t - $start_time;
                if($elapsed_send >= $send_interval) {
                    $start_time = time();
                    $hashes = $this->miningStat['hashes'] - $prev_hashes;
                    $prev_hashes = $this->miningStat['hashes'];
                    $this->sendStat($hashes, $height, $send_interval);
                }
			}

			if(!$blockFound || $elapsed <=0) {
				continue;
			}

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
                "version" => VERSION
            ];

            $this->miningStat['submits']++;
            $res = $this->sendHash($this->node, $postData, $response);
            $accepted = false;
            if($res) {
                $accepted = true;
            } else {
                if(is_array($this->miningNodes) && count($this->miningNodes)>0) {
                    foreach ($this->miningNodes as $node) {
                        $res = $this->sendHash($node, $postData, $response);
                        if($res) {
                            $accepted = true;
                            break;
                        }
                    }
                }
            }

            if($accepted) {
                //_log("Block confirmed", 1);
                $this->miningStat['accepted']++;
            } else {
                //_log("Block not confirmed: " . $res, 1);
                $this->miningStat['rejected']++;
            }

			sleep(3);

			if($this->block_cnt > 0 && $this->cnt >= $this->block_cnt) {
				break;
			}

			//_log("Mining stats: ".json_encode($this->miningStat), 2);
			$minerStatFile = Miner::getStatFile();
			file_put_contents($minerStatFile, json_encode($this->miningStat));

		}

		//_log("Miner stopped");
	}

    private function sendHash($node, $postData, &$response) {
        $res = url_post($node . "/mine.php?q=submitHash&", http_build_query($postData), 5);
        $response = json_decode($res, true);
        //_log("Send hash to node $node response = ".json_encode($response));
        if(!isset($this->miningStat['submitted_blocks'])) {
            $this->miningStat['submitted_blocks']=[];
        }
        $this->miningStat['submitted_blocks'][]=[
            "time"=>date("r"),
            "node"=>$node,
            "height"=>$postData['height'],
            "elapsed"=>$postData['elapsed'],
            "hashes"=>$this->attempt,
            "hit"=> $postData['hit'],
            "target"=>$postData['target'],
            "status"=>@$response['status']=="ok" ? "accepted" : "rejected",
            "response"=>@$response['data']
        ];
        if (@$response['status'] == "ok") {
            return true;
        } else {
            return false;
        }
    }

	static function getStatFile() {
		$file = getcwd() . "/miner_stat.json";
		return $file;
	}

}

const DEFAULT_CHAIN_ID = "01";
const MINER_VERSION = "1.5";

$node = @$argv[1];
$address = @$argv[2];
$cpu = @$argv[3];
$block_cnt = @$argv[4];

foreach ($argv as $item){
    if(strpos($item, "--threads")!==false) {
        $arr = explode("=", $item);
        $threads = $arr[1];
    }
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

function startMiner($address,$node, $forked) {
    global $cpu;
    $miner = new Miner($address, $node, $forked);
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
