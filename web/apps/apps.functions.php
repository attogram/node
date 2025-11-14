<?php

global $_config;

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

function display_message($message) {
    global $_config;

    if ($message === "") {
        return "";
    }

    $safe_message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

    if (empty($_config['enable_message_parsing'])) {
        return $safe_message;
    }

    $is_js = preg_match('/<script\b[^>]*>(.*?)<\/script>|on\w+\s*=\s*["\']|javascript:/is', $message);

    if ($is_js) {
        $encoded_script = base64_encode($message);
        $output = '<div>' . $safe_message . '</div>';
        $output .= '<div><strong style="color: red;">Security Review:</strong> This message contains JavaScript.</div>';
        $output .= '<button onclick="runScript(this)" data-script="' . $encoded_script . '">Run Script</button>';
    } else {
        $encoded_message = base64_encode($message);
        $output = '<span>' . $safe_message;
        $output .= ' <a href="javascript:void(0);" onclick="showMessageWarning(this)" data-message="' . $encoded_message . '">(Show raw)</a></span>';
    }

    return $output;
}
