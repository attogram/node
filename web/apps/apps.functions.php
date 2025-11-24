<?php

global $_config;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

function __($key) {
    $lang = $_SESSION['lang'] ?? 'en';
    $langFile = ROOT . '/web/lang/' . $lang . '.php';
    $key = strtolower(str_replace(' ', '_', $key));

    if (!file_exists($langFile)) {
        $langFile = ROOT . '/web/lang/en.php';
    }

    $translations = require($langFile);

    if (isset($translations[$key]) && !empty($translations[$key])) {
        return $translations[$key];
    } else {
        return $key;
    }
}

function truncate_hash($hash, $digits = 8) {
	if(empty($hash)) {
		return null;
	}
	$thash = substr($hash, 0, $digits) . "..." . substr($hash, -$digits);
	return '<span data-bs-toggle="tooltip" title="'.$hash.'">' . $thash . '</span>';
}

function explorer_address_link2($address, $short= false) {
	$text  = $address;
	if($short) {
		$text  = truncate_hash($address);
	}
	return '<a href="/apps/explorer/address.php?address='.$address.'">'.$text.'</a>';
}
