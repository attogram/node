<?php
require_once dirname(__DIR__)."/apps.inc.php";
require_once './Parsedown.php';

//error_reporting(E_ALL);
//ini_set('display_errors', 1);

class ParsedownExt extends Parsedown {
    private $docPath;

    public function __construct($docPath)
    {
        $this->docPath = $docPath;
    }

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

        $newDoc = $this->docPath . '/' . $href;
        $newDoc = $this->normalizePath($newDoc);

        if (strpos($newDoc, '../') === 0 || $newDoc === '..') {
            // This is an invalid link, pointing outside the docs directory.
            // Let's make it a dead link and style it to indicate it's broken.
            $link['element']['attributes']['href'] = '#';
            if (isset($link['element']['attributes']['class'])) {
                $link['element']['attributes']['class'] .= ' broken-link';
            } else {
                $link['element']['attributes']['class'] = 'broken-link';
            }
            $link['element']['attributes']['title'] = 'Invalid link (points outside of documentation)';
            return $link;
        }

        $link['element']['attributes']['href'] = "/apps/docs/index.php?doc=".$newDoc;
        return $link;
    }

    private function normalizePath($path)
    {
        if (empty($path)) {
            return '';
        }
        $parts = explode('/', $path);
        $absolutes = array();
        $num_dots = 0;
        foreach ($parts as $part) {
            if ('.' == $part || '' == $part) continue;
            if ('..' == $part) {
                if (count($absolutes) > 0) {
                    array_pop($absolutes);
                } else {
                    $num_dots++;
                }
            } else {
                $absolutes[] = $part;
            }
        }
        $prefix = str_repeat('../', $num_dots);
        return $prefix . implode('/', $absolutes);
    }
}

$docsDir = dirname(dirname(dirname(__DIR__)));
$baseDir = $docsDir.'/docs/';

if(!empty($_GET['doc'])) {
    $link = $_GET['doc'];
    $file = $baseDir . $link;
    if (is_dir($file)) {
        if (substr($link, -1) !== '/') {
            $link .= '/';
        }
        $file = $baseDir . $link . 'README.md';
    }
} else {
    $link = '';
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

$relativePath = str_replace($baseDir, '', $file);
$docPath = dirname($relativePath);
if ($docPath == ".") {
	$docPath = "";
}
$pd = new ParsedownExt($docPath);
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
