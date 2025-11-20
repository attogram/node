<?php
/**
 * PHPCoin Self-Contained Miner
 *
 * This script is a standalone miner for the PHPCoin cryptocurrency. It is designed to be run from the command line
 * and does not require any external dependencies other than the standard PHP extensions.
 *
 * Usage:
 * php miner.self.php -n <node_url> -a <your_address> [options]
 * php miner.self.php --node=<node_url> --address=<your_address> [options]
 *
 * Configuration:
 * A `miner.conf` file can be placed in the same directory as the script to set default values.
 * Command-line options will override any values set in the config file.
 *
 * Example miner.conf:
 * node = http://localhost:8000
 * address = PX...
 * cpu = 75
 * threads = 4
 *
 * Options:
 *   -n, --node=<url>        The URL of the PHPCoin node to connect to.
 *   -a, --address=<address> The PHPCoin address to mine rewards to.
 *   -c, --cpu=<percent>     The percentage of CPU to use (0-100). Default: 50.
 *   -t, --threads=<num>     The number of threads to use for mining. Default: 1.
 */

if(php_sapi_name() !== 'cli') exit;

//
// Startup Environment Check
//

function check_environment() {
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        die("Error: PHP version 7.4 or higher is required.\n");
    }

    $required_extensions = ['curl', 'gmp', 'pcntl'];
    foreach ($required_extensions as $ext) {
        if (!extension_loaded($ext)) {
            die("Error: The required PHP extension '$ext' is not installed or enabled.\n");
        }
    }
}
check_environment();

//
// Blockchain & Miner Configuration Constants
//

const DEVELOPMENT = false;
const BLOCK_TIME = 60;
const BLOCK_TARGET_MUL = 1000;
const MINER_VERSION = "1.5";
const VERSION = "1.6.8"; // This is used in sendStat and submitBlock
const HASHING_ALGO = PASSWORD_ARGON2I;


//
// Main Execution Logic
//

// 1. Set default config values
$config = [
    'node' => null,
    'address' => null,
    'cpu' => 50,
    'threads' => 1,
];

// 2. Load config from miner.conf file
if(file_exists(getcwd()."/miner.conf")) {
	$minerConf = parse_ini_file(getcwd()."/miner.conf");
	foreach($minerConf as $key => $value) {
		if(isset($config[$key])) {
			$config[$key] = $value;
		}
	}
}

// 3. Parse command-line arguments, overriding config file and defaults
$options = getopt(
    "n:a:c:t:", // Short options
    [
        "node:",
        "address:",
        "cpu:",
        "threads:",
    ]
);
foreach($options as $key => $value) {
    $key = str_replace(['n', 'a', 'c', 't'], ['node', 'address', 'cpu', 'threads'], $key);
    $config[$key] = $value;
}


// 4. Assign variables and validate
$node = $config['node'];
$address = $config['address'];
$cpu = (int)$config['cpu'];
$threads = (int)$config['threads'];

if($cpu > 100) $cpu = 100;

echo "PHPCoin Miner Version ".MINER_VERSION.PHP_EOL;
echo "Mining server:  ".$node.PHP_EOL;
echo "Mining address: ".$address.PHP_EOL;
echo "CPU:            ".$cpu.PHP_EOL;
echo "Threads:        ".$threads.PHP_EOL;


if(empty($node) || empty($address)) {
	die("Usage: php miner.self.php --node=<node> --address=<address> [--cpu=<cpu>] [--threads=<threads>]".PHP_EOL);
}

// 5. Verify node communication and public key
$res = Miner::url_get($node . "/api.php?q=getPublicKey&address=".$address);
if(empty($res)) {
	die("No response from node".PHP_EOL);
}
$res = json_decode($res, true);
if(empty($res) || $res['status'] != "ok" || empty($res['data'])) {
	die("Invalid response from node: ".json_encode($res).PHP_EOL);
}

echo "Network:        ".$res['network'].PHP_EOL;

// 6. Start the miner
$miner = new Miner($address, $node, false);
$miner->cpu = $cpu;
if($threads == 1) {
    $miner->start();
} else {
    $miner->fork($threads);
}


//
// Class Definitions
//

class Miner {

	public $address;
	public $node;
	public $cpu = 25;
    private $forked;

	private $running = true;

    private $hashing_time = 0;
    private $hashing_cnt = 0;
    private $speed;
    private $sleep_time;
    private $attempt;

    private $miningNodes = [];
    private $miningStat;
    private $minerid;

	function __construct($address, $node, $forked=false)
	{
		$this->address = $address;
		$this->node = $node;
        $this->minerid = time() . uniqid();
        $this->forked = $forked;
	}

    //
    // Public Methods
    //

    public function fork($threads) {
        for($i=1; $i<=$threads; $i++) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                die("Could not fork");
            } else if (!$pid) {
                // Child process
                $this->forked = true;
                $this->start();
                exit;
            }
        }
        // Parent process waits for all children to finish
        while (pcntl_waitpid(0, $status) != -1);
    }

	public function start() {
		$this->miningStat = [
			'started' => time(),
			'hashes' => 0,
			'submits' => 0,
			'accepted' => 0,
			'rejected' => 0,
			'dropped' => 0,
		];
		$this->sleep_time = (100 - $this->cpu) * 5;
		$this->getMiningNodes();

		while ($this->running) {
			$info = $this->getMiningInfo();
			if (!$this->isValidMiningInfo($info)) {
				sleep(3);
				continue;
			}

			$block = $this->initializeNewBlock($info);
			$this->findBlock($block, $info);
		}
	}

    //
    // Internal Methods
    //

	private function isValidMiningInfo($info) {
		if ($info === false || !isset($info['data']['generator'], $info['data']['ip'])) {
			return false;
		}
		if (!self::validateIp($info['data']['ip'])) {
			return false;
		}
		return true;
	}

	private function initializeNewBlock($info) {
        $block = new stdClass();
		$block->height = $info['data']['height'] + 1;
		$block->difficulty = $info['data']['difficulty'];
		$block->prevBlockId = $info['data']['block'];
        $block->miner = $this->address;
		return $block;
	}

	private function findBlock($block, $info) {
		$this->attempt = 0;
		$t1 = microtime(true);

		$solution = $this->hashingLoop($block, $info['data']['date'], $info['data']['time'], $t1, $info['data']['chain_id']);

		if ($solution) {
			$this->submitBlock($solution);
		}
	}

	private function hashingLoop($block, $block_date, $nodeTime, &$t1, $chain_id) {
		$offset = $nodeTime - time();
		$start_time = time();
		$prev_hashes = 0;

		while (true) {
			$this->attempt++;
			if ($this->sleep_time == INF) {
				$this->running = false;
				return null;
			}
			usleep($this->sleep_time * 1000);

			$now = time();
			$elapsed = $now - $offset - $block_date;

			if ($elapsed <= 0) {
				continue;
			}

			$th = microtime(true);
			$block->argon = $this->calculateArgonHash($block_date, $elapsed, $block->height);
			$block->nonce = $this->calculateNonce($block, $block_date, $elapsed, $chain_id);
			$hit = $this->calculateHit($block);
			$target = $this->calculateTarget($block->difficulty, $elapsed);

			$this->measureSpeed($t1, $th);
			$this->updateMiningStats($block->height, $elapsed, $hit, $target);

			// Send stats every 60 seconds
			$t = time();
			if ($t - $start_time > 60) {
				$hashes = $this->miningStat['hashes'] - $prev_hashes;
				$this->sendStat($hashes, $block->height, 60);
				$start_time = $t;
				$prev_hashes = $this->miningStat['hashes'];
			}

			if ($hit > 0 && $target > 0 && $hit > $target) {
				return [
					'argon' => $block->argon,
					'nonce' => $block->nonce,
					'height' => $block->height,
					'difficulty' => $block->difficulty,
					'date' => $block_date + $elapsed,
					'hit' => (string)$hit,
					'target' => (string)$target,
					'elapsed' => $elapsed,
				];
			}

			if ($this->hasNewBlock($block->prevBlockId)) {
				$this->miningStat['dropped']++;
				return null;
			}
		}
	}

	private function hasNewBlock($prev_block_id) {
		if ($this->attempt % 10 == 0) {
			$info = $this->getMiningInfo();
			if ($info !== false && $info['data']['block'] != $prev_block_id) {
				return true;
			}
		}
		return false;
	}

	private function submitBlock($solution) {
		$postData = [
			'argon' => $solution['argon'],
			'nonce' => $solution['nonce'],
			'height' => $solution['height'],
			'difficulty' => $solution['difficulty'],
			'address' => $this->address,
			'hit' => $solution['hit'],
			'target' => $solution['target'],
			'date' => $solution['date'],
			'elapsed' => $solution['elapsed'],
			'minerInfo' => 'phpcoin-miner cli ' . VERSION,
			"version" => VERSION
		];

		$this->miningStat['submits']++;
		$response = null;
		if ($this->sendHash($postData, $response)) {
			$this->miningStat['accepted']++;
		} else {
			$this->miningStat['rejected']++;
		}

		sleep(3);
		$minerStatFile = self::getStatFile();
		file_put_contents($minerStatFile, json_encode($this->miningStat));
	}

	private function updateMiningStats($height, $elapsed, $hit, $target) {
		global $argv;

		$status = sprintf(
			"PID:%-6s | Height:%-7s | Elapsed:%-5s | Speed:%-8s | Hit:%-10s | Target:%-10s | Submits:%-5s | Accepted:%-5s | Rejected:%-5s | Dropped:%-5s",
			getmypid(),
			$height,
			$elapsed,
			$this->speed . ' H/s',
			$hit,
			$target,
			$this->miningStat['submits'],
			$this->miningStat['accepted'],
			$this->miningStat['rejected'],
			$this->miningStat['dropped']
		);

		if(!$this->forked && !in_array("--flat-log", $argv)){
			echo $status . "\r";
		} else {
			echo $status . PHP_EOL;
		}
		$this->miningStat['hashes']++;
	}

    private function sendHash($postData, &$response) {
        $res = self::url_post($this->node . "/mine.php?q=submitHash&", http_build_query($postData), 5);
        $response = json_decode($res, true);
        if(!isset($this->miningStat['submitted_blocks'])) {
            $this->miningStat['submitted_blocks']=[];
        }
        $this->miningStat['submitted_blocks'][]=[
            "time"=>date("r"),
            "node"=>$this->node,
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


    //
    // Static Helper Methods (from Block, Peer, etc.)
    //

	private function calculateHit($block) {
		$base = $block->miner . "-" . $block->nonce . "-" . $block->height . "-" . $block->difficulty;
		$hash = hash("sha256", $base);
		$hash = hash("sha256", $hash);
		$hashPart = substr($hash, 0, 8);
		$value = self::gmp_hexdec($hashPart);
		$hit = gmp_div(gmp_mul(self::gmp_hexdec("ffffffff"), BLOCK_TARGET_MUL) , $value);
		return $hit;
	}

	private function calculateTarget($difficulty, $elapsed) {
		if($elapsed <= 0) {
			return 0;
		}
		$target = gmp_div(gmp_mul($difficulty , BLOCK_TIME), $elapsed);
		return $target;
	}

    private function calculateNonce($block, $prev_block_date, $elapsed, $chain_id) {
	    $nonceBase = "{$chain_id}{$block->miner}-{$prev_block_date}-{$elapsed}-{$block->argon}";
	    $calcNonce = hash("sha256", $nonceBase);
	    return $calcNonce;
    }

	private function calculateArgonHash($prev_block_date, $elapsed, $height) {
		$base = "{$prev_block_date}-{$elapsed}";
		$options = self::hashingOptions($height);
		if($height < 1614556800) { // UPDATE_3_ARGON_HARD
			$options['salt']=substr($this->address, 0, 16);
		}
		$argon = @password_hash(
			$base,
			HASHING_ALGO,
			$options
		);
		return $argon;
	}

	static function hashingOptions($height=null) {
		if($height < 1614556800) { // UPDATE_3_ARGON_HARD
			return ['memory_cost' => 2048, "time_cost" => 2, "threads" => 1];
		} else {
			return ['memory_cost' => 32768, "time_cost" => 2, "threads" => 1];
		}
	}

	static function validateIp($ip) {
		if(!DEVELOPMENT) {
			$ip = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_IPV4);
		}
		return $ip;
	}

    static function url_get($url,$timeout = 30) {
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
        curl_close ($ch);
        return $result;
    }

    static function url_post($url, $postdata, $timeout=30) {
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
        curl_close ($ch);
        return $result;
    }

    static function gmp_hexdec($n) {
        $gmp = gmp_init(0);
        $mult = gmp_init(1);
        for ($i=strlen($n)-1;$i>=0;$i--,$mult=gmp_mul($mult, 16)) {
            $gmp = gmp_add($gmp, gmp_mul($mult, hexdec($n[$i])));
        }
        return $gmp;
    }

	static function getStatFile() {
		$file = getcwd() . "/miner_stat.json";
		return $file;
	}
}
