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
 *   --flat-log              Enable flat logging for use in environments that do not support carriage returns.
 */

if(php_sapi_name() !== 'cli') exit;

//
// Blockchain & Miner Configuration Constants
//

const BLOCK_TIME = 60;
const BLOCK_TARGET_MUL = 1000;
const MINER_VERSION = "1.5";
const VERSION = "1.6.8"; // This is used in sendStat and submitBlock
const HASHING_ALGO = PASSWORD_ARGON2I;


//
// Class Definitions
//

class Utils {
    public static function validateIp($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_IPV4);
    }

    public static function url_get($url,$timeout = 30) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, 1);
        $result = curl_exec($ch);
        curl_close ($ch);
        return $result;
    }

    public static function url_post($url, $postdata, $timeout=30) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, 1);
        $result = curl_exec($ch);
        curl_close ($ch);
        return $result;
    }

    public static function getStatFile() {
        return getcwd() . "/miner_stat.json";
    }
}


class Crypto {
    public static function calculateHit($block) {
        $hash = hash("sha256", $block->miner . "-" . $block->nonce . "-" . $block->height . "-" . $block->difficulty);
        $hash = hash("sha256", $hash);
        $value = self::gmp_hexdec(substr($hash, 0, 8));
        return gmp_div(gmp_mul(self::gmp_hexdec("ffffffff"), BLOCK_TARGET_MUL) , $value);
    }

    public static function calculateTarget($difficulty, $elapsed) {
        if($elapsed <= 0) {
            return 0;
        }
        return gmp_div(gmp_mul($difficulty , BLOCK_TIME), $elapsed);
    }

    public static function calculateNonce($block, $prev_block_date, $elapsed, $chain_id) {
        return hash("sha256", "{$chain_id}{$block->miner}-{$prev_block_date}-{$elapsed}-{$block->argon}");
    }

    public static function calculateArgonHash($address, $prev_block_date, $elapsed, $height) {
        $options = self::hashingOptions($height);
        if($height < 1614556800) { // UPDATE_3_ARGON_HARD
            $options['salt']=substr($address, 0, 16);
        }
        $argon = password_hash("{$prev_block_date}-{$elapsed}", HASHING_ALGO, $options);
        if ($argon === false) {
            // Handle hash failure, perhaps log an error or die
            die("Error: password_hash failed.\n");
        }
        return $argon;
    }

    public static function hashingOptions($height=null) {
        if($height < 1614556800) { // UPDATE_3_ARGON_HARD
            return ['memory_cost' => 2048, "time_cost" => 2, "threads" => 1];
        } else {
            return ['memory_cost' => 32768, "time_cost" => 2, "threads" => 1];
        }
    }

    public static function gmp_hexdec($n) {
        $gmp = gmp_init(0);
        $mult = gmp_init(1);
        for ($i=strlen($n)-1;$i>=0;$i--,$mult=gmp_mul($mult, 16)) {
            $gmp = gmp_add($gmp, gmp_mul($mult, hexdec($n[$i])));
        }
        return $gmp;
    }
}


class MinerSetup {
    private $config;
    private $valid = false;

    public function __construct() {
        $this->checkEnvironment();
        $this->loadConfig();
        $this->parseArguments();
        $this->validateConfig();
    }

    private function checkEnvironment() {
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

    private function loadConfig() {
        // 1. Set default config values
        $this->config = [
            'node' => null,
            'address' => null,
            'cpu' => 50,
            'threads' => 1,
            'flat-log' => false,
        ];

        // 2. Load config from miner.conf file
        if(file_exists(getcwd()."/miner.conf")) {
            $minerConf = parse_ini_file(getcwd()."/miner.conf");
            foreach($minerConf as $key => $value) {
                if(isset($this->config[$key])) {
                    $this->config[$key] = $value;
                }
            }
        }
    }

    private function parseArguments() {
        $options = getopt(
            "n:a:c:t:", // Short options
            [
                "node:",
                "address:",
                "cpu:",
                "threads:",
                "flat-log",
            ]
        );
        $this->config['node'] = $options['node'] ?? $options['n'] ?? $this->config['node'];
        $this->config['address'] = $options['address'] ?? $options['a'] ?? $this->config['address'];
        $this->config['cpu'] = $options['cpu'] ?? $options['c'] ?? $this->config['cpu'];
        $this->config['threads'] = $options['threads'] ?? $options['t'] ?? $this->config['threads'];
        if(isset($options['flat-log'])) $this->config['flat-log'] = true;
    }

    private function validateConfig() {
        if ($this->config['cpu'] > 100) $this->config['cpu'] = 100;
        $this->config['cpu'] = (int)$this->config['cpu'];
        $this->config['threads'] = (int)$this->config['threads'];

        echo "PHPCoin Miner Version ".MINER_VERSION.PHP_EOL;
        echo "Mining server:  ".$this->config['node'].PHP_EOL;
        echo "Mining address: ".$this->config['address'].PHP_EOL;
        echo "CPU:            ".$this->config['cpu'].PHP_EOL;
        echo "Threads:        ".$this->config['threads'].PHP_EOL;

        if(empty($this->config['node']) || empty($this->config['address'])) {
            echo "Usage: php miner.self.php --node=<node> --address=<address> [--cpu=<cpu>] [--threads=<threads>] [--flat-log]".PHP_EOL;
            return;
        }

        // Verify node communication and public key
        $res = Utils::url_get($this->config['node'] . "/api.php?q=getPublicKey&address=".$this->config['address']);
        if(empty($res)) {
            echo "No response from node".PHP_EOL;
            return;
        }
        $res = json_decode($res, true);
        if(empty($res) || $res['status'] != "ok" || empty($res['data'])) {
            echo "Invalid response from node: ".json_encode($res).PHP_EOL;
            return;
        }

        echo "Network:        ".$res['network'].PHP_EOL;
        $this->valid = true;
    }

    public function isValid() {
        return $this->valid;
    }

    public function getConfig() {
        return $this->config;
    }
}


class Miner {

	private $address;
	private $node;
	private $cpu;
    private $is_forked = false;
    private $use_flat_log;
    private $threads;

	private $is_running = true;

    private $hashing_start_time = 0;
    private $hash_count = 0;
    private $speed = 0;
    private $sleep_time;
    private $attempt_count = 0;

    private $mining_stats;

	function __construct($config)
	{
		$this->address = $config['address'];
		$this->node = $config['node'];
        $this->cpu = $config['cpu'];
        $this->use_flat_log = $config['flat-log'];
        $this->threads = $config['threads'];
	}

    /**
     * Forks the miner process to run on multiple threads.
     */
    public function fork() {
        for($i=1; $i<=$this->threads; $i++) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                die("Could not fork");
            } else if (!$pid) {
                // This is the child process
                $this->is_forked = true;
                $this->start();
                exit;
            }
        }
        // Parent process waits for all children to complete
        while (pcntl_waitpid(0, $status) != -1);
    }

    /**
     * Starts the main mining loop.
     */
	public function start() {
		$this->mining_stats = [
			'started' => time(), 'hashes' => 0, 'submits' => 0,
			'accepted' => 0, 'rejected' => 0, 'dropped' => 0,
		];
		$this->sleep_time = (100 - $this->cpu) * 5;

		while ($this->is_running) {
			$info = $this->getMiningInfo();
			if (!$this->isValidMiningInfo($info)) {
				sleep(3); // Wait before retrying
				continue;
			}

			$block = $this->initializeNewBlock($info);
			$this->findBlockSolution($block, $info);
		}
	}

	private function isValidMiningInfo($info) {
		if ($info === false || !isset($info['data']['generator'], $info['data']['ip'])) {
			return false;
		}
		if (!Utils::validateIp($info['data']['ip'])) {
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

	private function findBlockSolution($block, $info) {
		$this->attempt_count = 0;
		$this->hashing_start_time = microtime(true);

		$solution = $this->hashingLoop($block, $info['data']['date'], $info['data']['time'], $info['data']['chain_id']);

		if ($solution) {
			$this->submitBlock($solution);
		}
	}

	private function hashingLoop($block, $block_date, $nodeTime, $chain_id) {
		$offset = $nodeTime - time();

		while (true) {
			$this->attempt_count++;
			if ($this->sleep_time === INF) {
				$this->is_running = false;
				return null;
			}
			usleep($this->sleep_time * 1000);

			$now = time();
			$elapsed = $now - $offset - $block_date;

			if ($elapsed <= 0) {
				continue;
			}

			$hash_time_start = microtime(true);
			$block->argon = Crypto::calculateArgonHash($this->address, $block_date, $elapsed, $block->height);
			$block->nonce = Crypto::calculateNonce($block, $block_date, $elapsed, $chain_id);
			$hit = Crypto::calculateHit($block);
			$target = Crypto::calculateTarget($block->difficulty, $elapsed);

			$this->measureSpeed($hash_time_start);
			$this->updateMiningStats($block->height, $elapsed, $hit, $target);

			if ($hit > 0 && $target > 0 && $hit > $target) {
				return [
					'argon' => $block->argon, 'nonce' => $block->nonce, 'height' => $block->height,
					'difficulty' => $block->difficulty, 'date' => $block_date + $elapsed,
					'hit' => (string)$hit, 'target' => (string)$target, 'elapsed' => $elapsed,
				];
			}

			if ($this->hasNewBlock($block->prevBlockId)) {
				$this->mining_stats['dropped']++;
				return null;
			}
		}
	}

    private function measureSpeed($hash_time_start) {
        $hash_time_end = microtime(true);
        $this->hashing_start_time += ($hash_time_end - $hash_time_start);
        $this->hash_count++;
        $total_time = $hash_time_end - $this->hashing_start_time;
        if($total_time > 0) {
            $this->speed = number_format($this->hash_count / $total_time, 2);
        }
    }

	private function hasNewBlock($prev_block_id) {
		// Check for a new block on the network every 10 attempts
		if ($this->attempt_count % 10 == 0) {
			$info = $this->getMiningInfo();
			if ($info !== false && $info['data']['block'] != $prev_block_id) {
				return true;
			}
		}
		return false;
	}

    private function getMiningInfo() {
        return json_decode(Utils::url_get($this->node . "/mine.php?q=info"), true);
    }

	private function submitBlock($solution) {
		$postData = [
			'argon' => $solution['argon'], 'nonce' => $solution['nonce'], 'height' => $solution['height'],
			'difficulty' => $solution['difficulty'], 'address' => $this->address, 'hit' => $solution['hit'],
			'target' => $solution['target'], 'date' => $solution['date'], 'elapsed' => $solution['elapsed'],
			'minerInfo' => 'phpcoin-miner cli ' . VERSION, "version" => VERSION
		];

		$this->mining_stats['submits']++;
		$response = Utils::url_post($this->node . "/mine.php?q=submitHash&", http_build_query($postData), 5);
        $response_data = json_decode($response, true);

		if (json_last_error() === JSON_ERROR_NONE && isset($response_data['status']) && $response_data['status'] == "ok") {
			$this->mining_stats['accepted']++;
		} else {
			$this->mining_stats['rejected']++;
		}

		sleep(3); // Wait before starting next block
		file_put_contents(Utils::getStatFile(), json_encode($this->mining_stats));
	}

	private function updateMiningStats($height, $elapsed, $hit, $target) {
		$status = sprintf(
			"PID:%-6s | Height:%-7s | Elapsed:%-5s | Speed:%-8s | Hit:%-10s | Target:%-10s | Submits:%-5s | Accepted:%-5s | Rejected:%-5s | Dropped:%-5s",
			getmypid(), $height, $elapsed, $this->speed . ' H/s', $hit, $target,
			$this->mining_stats['submits'], $this->mining_stats['accepted'],
			$this->mining_stats['rejected'], $this->mining_stats['dropped']
		);

		if(!$this->is_forked && !$this->use_flat_log){
			echo $status . "\r";
		} else {
			echo $status . PHP_EOL;
		}
		$this->mining_stats['hashes']++;
	}
}


//
// Main Execution Logic
//

$setup = new MinerSetup();
if (!$setup->isValid()) {
    exit(1);
}
$config = $setup->getConfig();

$miner = new Miner($config);

if($config['threads'] == 1) {
    $miner->start();
} else {
    $miner->fork();
}
