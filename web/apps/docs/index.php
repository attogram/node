<?php
require_once dirname(__DIR__)."/apps.inc.php";
require_once './Parsedown.php';

//error_reporting(E_ALL);
//ini_set('display_errors', 1);

class ParsedownExt extends Parsedown {
    protected function inlineLink($Excerpt)
    {
        $link = parent::inlineLink($Excerpt);
        $href = $link['element']['attributes']['href'];

        // Don't rewrite external links, mailto links, or anchors
        if (preg_match('/^(https?:\/\/|mailto:|#)/', $href)) {
            return $link;
        }

        // Don't rewrite links to non-markdown files
        $allowedExtensions = ['md', 'png', 'pdf'];
        $extension = pathinfo($href, PATHINFO_EXTENSION);
        if ($extension && !in_array(strtolower($extension), $allowedExtensions)) {
             return $link;
        }

        $link['element']['attributes']['href'] = "/apps/docs/index.php/".ltrim($href, './');
        return $link;
    }
}

$docsDir = dirname(dirname(dirname(__DIR__)));
$baseDir = $docsDir.'/docs/';

$link = '';
if (isset($_SERVER['REQUEST_URI'])) {
    $urlPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathPrefix = '/apps/docs/index.php/';
    if (strpos($urlPath, $pathPrefix) === 0) {
        $link = substr($urlPath, strlen($pathPrefix));
    }
}

if(!empty($link)) {
    $file = $baseDir . $link;
    if (is_dir($file)) {
        $file .= 'README.md';
    }
} else {
    $file = $baseDir . 'README.md';
}

// Security: Prevent path traversal
$realFile = realpath($file);
$realBaseDir = realpath($baseDir);

if ($realFile === false || strpos($realFile, $realBaseDir) !== 0) {
    define("PAGE", "Docs - Not Found");
    define("APP_NAME", "Docs");
    http_response_code(404);
    require_once __DIR__. '/../common/include/top.php';
    echo "<h1>404 Docs Not Found</h1>";
    require_once __DIR__ . '/../common/include/bottom.php';
    exit;
} else {
    $file = $realFile;
}

$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

if ($extension === 'png') {
    header('Content-Type: image/png');
    readfile($file);
    exit;
} elseif ($extension === 'pdf') {
    header('Content-Type: application/pdf');
    readfile($file);
    exit;
}

$pd = new ParsedownExt();
$pd->setSafeMode(true);
$text = file_get_contents($file);

define("PAGE", "Docs");
define("APP_NAME", "Docs");

?>
<?php
require_once __DIR__. '/../common/include/top.php';
?>

<?php echo $pd->text($text); ?>

<?php
require_once __DIR__ . '/../common/include/bottom.php';
?>
