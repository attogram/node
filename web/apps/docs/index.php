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
        if (pathinfo($href, PATHINFO_EXTENSION) && pathinfo($href, PATHINFO_EXTENSION) != 'md') {
             return $link;
        }

        $link['element']['attributes']['href'] = "/apps/docs/index.php/".ltrim($href, './');
        return $link;
    }
}

$docsDir = dirname(dirname(dirname(__DIR__)));
$baseDir = $docsDir.'/docs/';

$link = '';
if (isset($_SERVER['PATH_INFO'])) {
    $link = ltrim($_SERVER['PATH_INFO'], '/');
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
    // on attack, default to main readme
    $file = $realBaseDir . '/README.md';
} else {
    $file = $realFile;
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
