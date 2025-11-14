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
    static $js_included = false;

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

    if (!$js_included) {
        $output .= '
        <script type="text/javascript">
            function showMessageWarning(element) {
                if (confirm("Warning: Displaying the raw message could expose you to security risks like XSS attacks. Do you want to continue?")) {
                    const encodedMessage = element.getAttribute("data-message");

                    const rawMessageContainer = document.createElement("div");
                    rawMessageContainer.className = "raw-message-content";
                    rawMessageContainer.style.border = "1px solid red";
                    rawMessageContainer.style.padding = "5px";
                    rawMessageContainer.style.marginTop = "5px";

                    const messageContent = document.createElement("span");
                    messageContent.innerHTML = atob(encodedMessage);
                    rawMessageContainer.appendChild(messageContent);

                    const hideLink = document.createElement("a");
                    hideLink.href = "javascript:void(0);";
                    hideLink.innerText = "(Hide raw)";

                    const originalContent = element.parentNode.innerHTML;

                    hideLink.onclick = function() {
                        const newElement = document.createElement("span");
                        newElement.innerHTML = originalContent;
                        rawMessageContainer.replaceWith(newElement);
                    };

                    rawMessageContainer.appendChild(document.createElement("br"));
                    rawMessageContainer.appendChild(hideLink);

                    element.parentNode.replaceWith(rawMessageContainer);
                }
            }

            function runScript(element) {
                if (confirm("Security Warning: This message contains a script. Running it could expose your system to security risks. Are you sure you want to run this script?")) {
                    const encodedScript = element.getAttribute("data-script");

                    const scriptContainer = document.createElement("div");
                    scriptContainer.className = "script-container";

                    const script = document.createElement("script");
                    script.innerHTML = atob(encodedScript);

                    scriptContainer.appendChild(script);
                    element.parentNode.replaceWith(scriptContainer);
                }
            }
        </script>';
        $js_included = true;
    }

    return $output;
}
