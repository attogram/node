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
    $safe_message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    if (empty(trim($message))) {
        return "";
    }
    if (!$_config['enable_message_parsing']) {
        return $safe_message;
    }
    $contains_script = strpos($message, '<script>') !== false;
    $unique_id = 'msg-' . uniqid();
    $output = '<div id="' . $unique_id . '-safe">' . $safe_message;
    $output .= ' <a href="javascript:void(0);" onclick="showMessageWarning(\'' . $unique_id . '\')">(Show raw)</a></div>';
    $output .= '<div id="' . $unique_id . '-raw" style="display:none; border: 1px solid red; padding: 5px; margin-top: 5px;">';
    if ($contains_script) {
        $output .= '<p style="color:red; font-weight:bold;">Warning: This message contains &lt;script&gt; tags and could be malicious.</p>';
    }
    $output .= $message;
    $output .= '<br/><a href="javascript:void(0);" onclick="hideRawMessage(\'' . $unique_id . '\')">(Hide raw)</a>';
    $output .= '</div>';
    if (!defined('MESSAGE_JS_INCLUDED')) {
        $output .= '
        <script type="text/javascript">
            function showMessageWarning(id) {
                if (confirm("Warning: Displaying the raw message could expose you to security risks like XSS attacks. Do you want to continue?")) {
                    document.getElementById(id + "-safe").style.display = "none";
                    document.getElementById(id + "-raw").style.display = "block";
                }
            }
            function hideRawMessage(id) {
                document.getElementById(id + "-safe").style.display = "block";
                document.getElementById(id + "-raw").style.display = "none";
            }
        </script>';
        define('MESSAGE_JS_INCLUDED', true);
    }
    return $output;
}
