<?php
echo '<pre>';
echo 'REQUEST_URI: ' . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "\n";
echo 'SCRIPT_NAME: ' . ($_SERVER['SCRIPT_NAME'] ?? 'Not set') . "\n";
echo 'QUERY_STRING: ' . ($_SERVER['QUERY_STRING'] ?? 'Not set') . "\n";
echo 'PATH_INFO: ' . ($_SERVER['PATH_INFO'] ?? 'Not set') . "\n";
echo 'PHP_SELF: ' . ($_SERVER['PHP_SELF'] ?? 'Not set') . "\n";
echo '</pre>';
