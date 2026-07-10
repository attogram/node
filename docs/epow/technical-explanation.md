[PHPCoin Docs](../) > [ePoW](./)


---

# ePoW (Elapsed Proof of Work)

## Table of Contents
- [A Simple Explanation](#a-simple-explanation)
- [Technical Workflow](#technical-workflow)
- [Code Examples and Source Files](#code-examples-and-source-files)

---

## A Simple Explanation

ePoW, or Elapsed Proof of Work, is the consensus mechanism that keeps the phpcoin network secure and fair. If you're familiar with Bitcoin's Proof of Work (PoW), you'll find ePoW has some interesting differences.

In a traditional PoW system, miners are given a single puzzle for each new block, and its difficulty is static while they work on it.

The defining feature of ePoW is completely different: the puzzle gets easier to solve with every passing second.

**Here's the core idea:**

To use an analogy:
*   **Traditional PoW** gives miners one single, very hard puzzle to solve.
*   **phpcoin's ePoW** gives miners a new puzzle every second, with each new puzzle being a little bit easier than the last.

This unique mechanism is driven by the "elapsed time" since the last block was found. As miners work, this elapsed time increases, and for each second that passes, the mining target becomes easier to hit. This means the longer a block goes unsolved, the easier it becomes to solve.

This has two key effects:
*   **Stabilizes Block Time:** It ensures that blocks are found, on average, every 60 seconds. If a block isn't found quickly, the constantly decreasing difficulty makes it more and more likely to be found as time goes on.
*   **Fairness:** It reduces the advantage of large mining pools. The difficulty adjusts so rapidly that it creates a more level playing field for all miners on the network.

In short, ePoW is a more responsive version of PoW that uses the "elapsed time" between blocks to regulate the network's block time and difficulty. This helps to maintain a fair and stable environment for all participants in the phpcoin network.

<!-- Diagram placeholder: An SVG diagram illustrating the relationship between elapsed time and mining difficulty -->

---

## Technical Workflow

The ePoW consensus mechanism is a process that involves miners and the node working together to create and validate new blocks. Here's a step-by-step breakdown of the technical workflow.

### 1. Block Creation and Mining Information

When a miner is ready to mine a new block, they first request mining information from a phpcoin node. The node provides the following critical data:

*   The `height` of the new block.
*   The `difficulty` for the current block.
*   The `date` (timestamp) of the previous block.

### 2. The Role of "Elapsed Time"

The core of ePoW is the concept of "elapsed time." This is the time, in seconds, that has passed since the previous block was mined. The miner calculates this value continuously as they are working on a new block.

**Pseudo-code:**
```
previous_block_date = node.get_latest_block().date
current_timestamp = get_current_time()
elapsed_time = current_timestamp - previous_block_date
```

The `elapsed_time` is crucial because it directly influences the mining `target`.

### 3. Calculating the Mining Target

In phpcoin, a **higher** `hit` value is better. To find a valid block, a miner's `hit` must be greater than the `target`. The `target` is dynamically calculated using the `difficulty` and the `elapsed` time.

**Pseudo-code:**
```
target = (difficulty * BLOCK_TIME) / elapsed_time
```

*   `BLOCK_TIME`: A constant, which is 60 seconds in phpcoin.
*   `difficulty`: The difficulty of the current block.
*   `elapsed_time`: The time passed since the last block.

This formula means that as `elapsed_time` increases, the `target` decreases, making it easier to find a valid block. Conversely, if a block is found quickly, `elapsed_time` is small, the `target` is high, and it's harder to find a valid block.

### 4. Hashing and Finding a Valid Block

In ePoW, the mining process is an iteration over **hash attempts**, where miners continuously generate new hashes until they find a `hit` that exceeds the `target`. While the `target` value only changes once per second (with the `elapsed_time`), miners can perform many thousands or millions of hash attempts within that second.

This is possible because the hashing process introduces randomness, allowing each attempt to be unique.

Here is the mining loop:

1.  **Outer Loop (Time-based):** For each second that passes, the miner updates the `elapsed_time` and recalculates the `target`.
2.  **Inner Loop (Hashing):** Within each second, the miner performs as many hashing attempts as their hardware allows.
3.  **Generate a Random Argon Hash:** For each attempt, a new **Argon2id hash** is calculated. The Argon2id algorithm automatically generates a **random salt** for each hash. This randomness is the key to getting a different result on each attempt.
4.  **Calculate a Deterministic Nonce:** The new, random `argon_hash` is used to calculate a deterministic `nonce`. While the `nonce` calculation itself is deterministic, the randomness of its input (`argon_hash`) ensures the `nonce` is unique for each attempt.
5.  **Calculate the Hit:** The miner calculates the `hit` value based on this unique `nonce`.
6.  **Check Against Target:** If `hit > target`, a valid block has been found. The miner submits the block with the successful `elapsed_time`, the `argon` hash, and the `nonce` that produced the winning `hit`.

**Pseudo-code:**
```
previous_block_date = node.get_latest_block().date

while (true):
    // Outer loop: updated each second
    current_timestamp = get_current_time()
    elapsed_time = current_timestamp - previous_block_date
    target = (difficulty * BLOCK_TIME) / elapsed_time

    // Inner loop: runs as fast as possible
    while (get_current_time() - current_timestamp < 1):
        // Each call to argon2 produces a different hash due to random salt
        argon_hash = argon2("previous_block_date-elapsed_time")

        nonce = sha256("miner_address-previous_block_date-elapsed_time-argon_hash")

        hit = calculate_hit(miner_address, nonce, block_height, difficulty)

        if (hit > target):
            // Block found!
            submit_block(nonce, argon_hash, elapsed_time)
            exit_loops() // Exit both inner and outer loops
```

#### Calculating the Hit

The `hit` value is a crucial part of ePoW, representing the quality of a single hash attempt. It's a number that must exceed the current `target` to solve the block. The `hit` is unique to each attempt and to each miner.

Here's why each `hit` is different:
*   **For the same miner:** A miner can generate thousands of `hit` values per second because each call to the Argon2id hashing function produces a new, random `argon_hash`. This changes the `nonce`, which in turn changes the `hit`.
*   **For different miners:** Even with the same `elapsed_time` and `difficulty`, two different miners will always calculate different `hit` values. This is because their unique `miner_address` is a key component of the `nonce` calculation, ensuring they are not working on the same puzzle.

The calculation process is as follows:

1.  **Create a Base String:** A unique string is created for each attempt, combining the miner's address, the current `nonce`, the block `height`, and the current `difficulty`.
    -   `base = miner_address + "-" + nonce + "-" + height + "-" + difficulty`

2.  **Double SHA-256 Hash:** The `base` string is hashed twice using the SHA-256 algorithm.
    -   `hash = sha256(sha256(base))`

3.  **Extract a Hash Part:** The first 8 characters (4 bytes) of the resulting hexadecimal hash are extracted.
    -   `hash_part = substring(hash, 0, 8)`

4.  **Convert to a Number:** This hexadecimal `hash_part` is converted into a numerical value.
    -   `value = hex_to_decimal(hash_part)`

5.  **Calculate the Final Hit:** The final `hit` is calculated using a formula that scales the `value`. `BLOCK_TARGET_MUL` is a constant with a value of `1000`.
    -   `hit = (0xffffffff * BLOCK_TARGET_MUL) / value`


**Pseudo-code for `calculate_hit`:**
```
function calculate_hit(miner_address, nonce, block_height, difficulty):
    // 1. Create the base string
    base_string = miner_address + "-" + nonce + "-" + block_height + "-" + difficulty

    // 2. Double SHA-256 hash
    hash1 = sha256(base_string)
    hash2 = sha256(hash1)

    // 3. Extract the first 8 characters
    hash_part = substring(hash2, 0, 8)

    // 4. Convert hex to decimal
    value = hex_to_decimal(hash_part)

    // 5. Calculate the final hit
    // Note: 0xffffffff is the maximum 32-bit unsigned integer
    BLOCK_TARGET_MUL = 1000
    hit = (0xffffffff * BLOCK_TARGET_MUL) / value

    return hit
```

### 5. Submitting a Block

Once a miner finds a valid `nonce` and `argon` hash, they submit them along with the `elapsed` time and their address to the node. This is typically done via an API call to `mine.php`.

### 6. Block Validation by the Node

The node receives the submitted block data and performs a series of checks to validate it:

1.  **Verify Elapsed Time**: The node checks if the `elapsed` time is valid (i.e., greater than zero).
2.  **Verify Argon Hash**: The node recalculates the Argon2 hash using the previous block's date and the submitted `elapsed` time. It then compares this to the `argon` hash submitted by the miner.
3.  **Verify Nonce**: The node recalculates the `nonce` using the miner's address, the previous block's date, the `elapsed` time, and the submitted `argon` hash. It checks if this matches the `nonce` sent by the miner.
4.  **Verify Hit and Target**: The node calculates the `hit` using the submitted data and recalculates the `target` using the `elapsed` time. It then verifies that `hit > target`.

If all of these checks pass, the node considers the block valid, adds it to the blockchain, and propagates it to other peers on the network.

---

## Code Examples and Source Files

Here are some relevant code snippets from the phpcoin source code that illustrate the ePoW mechanism.

### Calculating the Target

This function from `include/class/Block.php` shows how the target is calculated based on the `elapsed` time and `difficulty`.

```php
function calculateTarget($elapsed) {
    global $_config;
    if($elapsed == 0) {
        return 0;
    }
    $target = gmp_div(gmp_mul($this->difficulty , BLOCK_TIME), $elapsed);
    if($target == 0 && DEVELOPMENT) {
        $target = 1;
    }
    if($target > 100 && DEVELOPMENT) {
        $target = 100;
    }
    return $target;
}
```
[Link to `include/class/Block.php`](../../include/class/Block.php)

### Calculating the Nonce and Argon Hash

These functions from `include/class/Block.php` are used to calculate and verify the `nonce` and `argon` hash.

```php
function calculateNonce($prev_block_date, $elapsed, $chain_id = CHAIN_ID) {
    $nonceBase = "{$chain_id}{$this->miner}-{$prev_block_date}-{$elapsed}-{$this->argon}";
    $calcNonce = hash("sha256", $nonceBase);
    _log("calculateNonce nonceBase=$nonceBase argon={$this->argon} calcNonce=$calcNonce", 5);
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
```
[Link to `include/class/Block.php`](../../include/class/Block.php)

### Block Submission

When a miner submits a block, the `web/mine.php` file handles the request. This snippet shows how the submitted data is received and used to create a new `Block` object.

```php
$nonce = san($_POST['nonce']);
$version = Block::versionCode($height);
$address = san($_POST['address']);
$elapsed = intval($_POST['elapsed']);
$difficulty = san($_POST['difficulty']);
$argon = $_POST['argon'];

// ...

$block = new Block($generator, $address, $height, $date, $nonce, $data, $difficulty, $version, $argon, $prev_block['id']);
```
[Link to `web/mine.php`](../../web/mine.php)

### Block Validation (Mine Check)

The `mine()` function in `include/class/Block.php` is where the core validation logic for a new block resides. This snippet shows the checks for the `argon` hash, `nonce`, `hit`, and `target`.

```php
public function mine(&$err=null)
{
    // ...
    if(!$this->verifyArgon($prev_date, $elapsed)) {
        throw new Exception("Invalid argon={$this->argon}");
    }

    $calcNonce = $this->calculateNonce($prev_date, $elapsed);
    // ... (nonce comparison logic)

    $hit = $this->calculateHit();
    $target = $this->calculateTarget($elapsed);
    $res = $this->checkHit($hit, $target, $this->height);
    if(!$res && $this->height > UPDATE_3_ARGON_HARD) {
        throw new Exception("invalid hit or target");
    }

    return true;
}
```
[Link to `include/class/Block.php`](../../include/class/Block.php)
