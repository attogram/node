<?php

//
// START COPIED CODE
//

//
// Source: include/coinspec.inc.php
//
const CHAIN_PREFIX = "38";

//
// Source: include/class/Account.php
//
function generateAccount()
{
    // using secp256k1 curve for ECDSA
    $args = [
        "curve_name"       => "secp256k1",
        "private_key_type" => OPENSSL_KEYTYPE_EC,
    ];

    // generates a new key pair
    $key1 = openssl_pkey_new($args);

    // exports the private key encoded as PEM
    openssl_pkey_export($key1, $pvkey);

    if(PHP_VERSION_ID > 80000) {
        $in = sys_get_temp_dir() . "/phpcoin.in.pem";
        $out = sys_get_temp_dir() . "/phpcoin.out.pem";
        file_put_contents($in, $pvkey);
        $cmd = "openssl ec -in $in -out $out >/dev/null 2>&1";
        shell_exec($cmd);
        $pvkey = file_get_contents($out);
        unlink($in);
        unlink($out);
    }

    // converts the PEM to a base58 format
    $private_key = pem2coin($pvkey);

    // exports the private key encoded as PEM
    $pub = openssl_pkey_get_details($key1);

    // converts the PEM to a base58 format
    $public_key = pem2coin($pub['key']);

    // generates the account's address based on the public key
    $address = getAddress($public_key);
    return ["address" => $address, "public_key" => $public_key, "private_key" => $private_key];
}

function getAddress($public_key) {
    if(empty($public_key)) return null;
    $hash1=hash('sha256', $public_key);
    $hash2=hash('ripemd160',$hash1);
    $baseAddress=CHAIN_PREFIX.$hash2;
    $checksumCalc1=hash('sha256', $baseAddress);
    $checksumCalc2=hash('sha256', $checksumCalc1);
    $checksumCalc3=hash('sha256', $checksumCalc2);
    $checksum=substr($checksumCalc3, 0, 8);
    $addressHex = $baseAddress.$checksum;
    $address = base58_encode(hex2bin($addressHex));
    return $address;
}

//
// Source: include/functions.inc.php
//
function pem2coin($data)
{
    $data = str_replace("-----BEGIN PUBLIC KEY-----", "", $data);
    $data = str_replace("-----END PUBLIC KEY-----", "", $data);
    $data = str_replace("-----BEGIN EC PRIVATE KEY-----", "", $data);
    $data = str_replace("-----END EC PRIVATE KEY-----", "", $data);
    $data = str_replace("\n", "", $data);
    $data = base64_decode($data);
    return base58_encode($data);
}

//
// Source: include/common.functions.php
// Base58 encoding/decoding functions - all credits go to https://github.com/stephen-hill/base58php
//
function base58_encode($string)
{
	$alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
	$base = strlen($alphabet);
	// Type validation
	if (is_string($string) === false) {
		return false;
	}
	// If the string is empty, then the encoded string is obviously empty
	if (strlen($string) === 0) {
		return '';
	}
	// Now we need to convert the byte array into an arbitrary-precision decimal
	// We basically do this by performing a base256 to base10 conversion
	$hex = unpack('H*', $string);
	$hex = reset($hex);
	$decimal = gmp_init($hex, 16);
	// This loop now performs base 10 to base 58 conversion
	// The remainder or modulo on each loop becomes a base 58 character
	$output = '';
	while (gmp_cmp($decimal, $base) >= 0) {
		list($decimal, $mod) = gmp_div_qr($decimal, $base);
		$output .= $alphabet[gmp_intval($mod)];
	}
	// If there's still a remainder, append it
	if (gmp_cmp($decimal, 0) > 0) {
		$output .= $alphabet[gmp_intval($decimal)];
	}
	// Now we need to reverse the encoded data
	$output = strrev($output);
	// Now we need to add leading zeros
	$bytes = str_split($string);
	foreach ($bytes as $byte) {
		if ($byte === "\x00") {
			$output = $alphabet[0].$output;
			continue;
		}
		break;
	}
	return (string)$output;
}

function base58_decode($base58)
{
	$alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
	$base = strlen($alphabet);

	// Type Validation
	if (is_string($base58) === false) {
		return false;
	}
	// If the string is empty, then the decoded string is obviously empty
	if (strlen($base58) === 0) {
		return '';
	}
	$indexes = array_flip(str_split($alphabet));
	$chars = str_split($base58);
	// Check for invalid characters in the supplied base58 string
	foreach ($chars as $char) {
		if (isset($indexes[$char]) === false) {
			return false;
		}
	}
	// Convert from base58 to base10
	$decimal = gmp_init($indexes[$chars[0]], 10);
	for ($i = 1, $l = count($chars); $i < $l; $i++) {
		$decimal = gmp_mul($decimal, $base);
		$decimal = gmp_add($decimal, $indexes[$chars[$i]]);
	}
	// Convert from base10 to base256 (8-bit byte array)
	$output = '';
	while (gmp_cmp($decimal, 0) > 0) {
		list($decimal, $byte) = gmp_div_qr($decimal, 256);
		$output = pack('C', gmp_intval($byte)).$output;
	}
	// Now we need to add leading zeros
	foreach ($chars as $char) {
		if ($indexes[$char] === 0) {
			$output = "\x00".$output;
			continue;
		}
		break;
	}
	return $output;
}

//
// END COPIED CODE
//

const VANITYGEN_NAME = 'PHPCoin Vanity Address Generator';
const VANITYGEN_VERSION = '0.0.1';
const VANITYGEN_USAGE = 'Usage: php vanitygen.php prefix [-c] [-d]' . PHP_EOL .
    '  prefix     Prefix for the PHPCoin address (e.g., "Php")' . PHP_EOL .
    '  -c         Case sensitive matching' . PHP_EOL .
    '  -d         Enable debug output' . PHP_EOL;
const VANITYGEN_URL = 'https://github.com/phpcoinn/node/blob/main/utils/vanitygen.php';

$debug = false;

print VANITYGEN_NAME . ' v' . VANITYGEN_VERSION . PHP_EOL;

if (php_sapi_name() !== 'cli') {
    exit('ERROR: This script must be run from the command line' . PHP_EOL);
};

generateVanityAddress(getOptionsOrExit($argv));

print PHP_EOL . 'Exiting ' . VANITYGEN_NAME . PHP_EOL;

/**
 * Generates a vanity PHPCoin address based on the provided options.
 *
 * @param array $options An associative array with keys:
 *                       - 'prefix': The desired prefix for the address.
 *                       - 'case_sensitive': Boolean indicating if the match should be case sensitive.
 * @return array The generated account details containing 'address', 'public_key', and 'private_key'.
 */
function generateVanityAddress(array $options): array
{
    $prefix = $options['prefix'];
    // All PHPCoin addresses start with uppercase 'P'
    if (! str_starts_with($prefix, 'p') && ! str_starts_with($prefix, 'P')) {
        $prefix = 'P' . $prefix;
    }
    // Force starting with uppercase 'P'
    if (str_starts_with($prefix, 'p')) {
        $prefix = 'P' . substr($prefix, 1);
    }

    $caseSensitive = $options['case_sensitive'];

    print 'Prefix: ' . $prefix . PHP_EOL;
    print 'Case Sensitive: ' . ($caseSensitive ? 'Yes' : 'No') . PHP_EOL;

    $count = 0;

    while (true) {
        $account = generateAccount();
        $address = $account['address'];
        $count++;
        _debug('Generation '. $count . ': ' . $address);

        if (! $caseSensitive) {
            $address = strtolower($address);
            $prefix = strtolower($prefix);
        }
        if (str_starts_with($address, $prefix)) {
            print 'Found vanity PHPCoin address after '. $count . ' tries!' . PHP_EOL;
            print_r($account);
            return $account;
        }
        if ($count % 500 === 0) {
            print 'Generated ' . $count . ' PHPCoin addresses...' . PHP_EOL;
        }
    }
}

/**
 * Parses command-line arguments into options and positional arguments.
 *
 * Supports:
 * - Positional arguments (e.g., "prefix")
 * - Long options (e.g., --foo)
 * - Long options with value (e.g., --value=foo or --value foo)
 * - Short options (e.g., -f)
 * - Short options with value (e.g., -v=foo or -v foo)
 */
function getOptionsOrExit(array $argv): array
{
    global $debug;

    $options = [];
    $arguments = [];

    // Start at 1 to skip the script name ($argv[0])
    for ($i = 1; $i < count($argv); $i++) {
        $item = $argv[$i];
        if (strpos($item, '--') === 0) { // 1. Long Option: --key or --key=value or --key value
            $key = substr($item, 2);
            $value = true; // Default for flags like --verbose
            if (strpos($key, '=') !== false) { // Check for --key=value format
                list($key, $value) = explode('=', $key, 2);
            } else if (isset($argv[$i + 1]) && strpos($argv[$i + 1], '-') !== 0) {
                // Check for --key value format
                // Is there a next item? AND Is the next item NOT another option?
                $value = $argv[$i + 1];
                $i++; // Skip the next item, it's been consumed as a value
            }
            $options[$key] = $value;
        } else if (strpos($item, '-') === 0) { // 2. Short Option: -k or -k=value or -k value
            $key = substr($item, 1);
            $value = true; // Default for flags like -v
            // Check for -k=value format
            if (strpos($key, '=') !== false) {
                list($key, $value) = explode('=', $key, 2);
            }
            // Check for -k value format
            // Is there a next item? AND Is the next item NOT another option?
            else if (isset($argv[$i + 1]) && strpos($argv[$i + 1], '-') !== 0) {
                $value = $argv[$i + 1];
                $i++; // Skip the next item
            }
            $options[$key] = $value;
        } else { // 3. Positional Argument
            $arguments[] = $item;
        }
    }

    if (empty($options) && empty($arguments)) {
        exit(VANITYGEN_USAGE . PHP_EOL);
    }

    if (empty($arguments[0])) {
        exit('ERROR: No prefix provided.' . PHP_EOL . VANITYGEN_USAGE . PHP_EOL);
    }

    if (isset($options['d'])) {
        $debug = true;
    }

    return [
        'prefix' => $arguments[0],
        'case_sensitive' => isset($options['c']) ? true : false,
    ];
}

/**
 * Outputs debug messages if debugging is enabled.
 *
 * @param string $message The debug message to output.
 */
function _debug(string $message): void
{
    global $debug;
    if ($debug) {
        print '[DEBUG] ' . $message . PHP_EOL;
    }
}
