<?php

class CryptoHelper
{
    // converts PEM key to hex
    public static function pem2hex($data)
    {
        $data = str_replace("-----BEGIN PUBLIC KEY-----", "", $data);
        $data = str_replace("-----END PUBLIC KEY-----", "", $data);
        $data = str_replace("-----BEGIN EC PRIVATE KEY-----", "", $data);
        $data = str_replace("-----END EC PRIVATE KEY-----", "", $data);
        $data = str_replace("\n", "", $data);
        $data = base64_decode($data);
        $data = bin2hex($data);
        return $data;
    }

    // converts hex key to PEM
    public static function hex2pem($data, $is_private_key = false)
    {
        $data = hex2bin($data);
        $data = base58_encode($data);
        $data = base64_encode($data);
        if ($is_private_key) {
            return "-----BEGIN EC PRIVATE KEY-----\n".$data."\n-----END EC PRIVATE KEY-----";
        }
        return "-----BEGIN PUBLIC KEY-----\n".$data."\n-----END PUBLIC KEY-----";
    }


    // converts PEM key to the base58 version used by PHP
    public static function pem2coin($data)
    {
        $data = str_replace("-----BEGIN PUBLIC KEY-----", "", $data);
        $data = str_replace("-----END PUBLIC KEY-----", "", $data);
        $data = str_replace("-----BEGIN EC PRIVATE KEY-----", "", $data);
        $data = str_replace("-----END EC PRIVATE KEY-----", "", $data);
        $data = str_replace("\n", "", $data);
        $data = base64_decode($data);


        return base58_encode($data);
    }

    public static function priv2pub($private_key) {
        $pk = self::coin2pem($private_key, true);
        $pkey = openssl_pkey_get_private($pk);
        $pub = openssl_pkey_get_details($pkey);
        $public_key = self::pem2coin($pub['key']);
        return $public_key;
    }

    public static function ec_verify($data, $signature, $key, $chain_id = CHAIN_ID)
    {
        // transform the base58 key to PEM
        $public_key = self::coin2pem($key);

        $data = $chain_id . $data;

        $signature = base58_decode($signature);

        $pkey = openssl_pkey_get_public($public_key);

        $res = openssl_verify($data, $signature, $pkey, OPENSSL_ALGO_SHA256);

        _log("Sign: verify signature for data: $data chain_id=$chain_id res=$res", 5);
        if ($res === 1) {
            return true;
        }
        return false;
    }

    // converts the key in base58 to PEM
    public static function coin2pem($data, $is_private_key = false)
    {
        $data = base58_decode($data);
        $data = base64_encode($data);

        $dat = str_split($data, 64);
        $data = implode("\n", $dat);

        if ($is_private_key) {
            return "-----BEGIN EC PRIVATE KEY-----\n".$data."\n-----END EC PRIVATE KEY-----\n";
        }
        return "-----BEGIN PUBLIC KEY-----\n".$data."\n-----END PUBLIC KEY-----\n";
    }
}
