<?php
$en = require 'web/lang/en.php';
$files = scandir('web/lang');
foreach ($files as $file) {
    if ($file === '.' || $file === '..' || $file === 'en.php' || $file === 'README.md') {
        continue;
    }
    $lang = substr($file, 0, -4);
    $file_path = "web/lang/$file";
    $dict = require $file_path;
    $missing = array_diff_key($en, $dict);
    if (count($missing) > 0) {
        echo "Missing keys in $lang:\n";
        print_r($missing);
    }
}
