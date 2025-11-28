<?php
require_once dirname(__DIR__)."/apps.inc.php";
require_once './Parsedown.php';

//error_reporting(E_ALL);
//ini_set('display_errors', 1);

class ParsedownExt extends Parsedown {
    private $docsDir;

    public function __construct($docsDir) {
        $this->docsDir = $docsDir;
    }

    function inlineLink($Excerpt)
    {
        $link = parent::inlineLink($Excerpt);

        if ( ! isset($link['element']['attributes']['href']))
        {
            return $link;
        }

        $href = $link['element']['attributes']['href'];

        if (strpos($href, 'http://') === 0 || strpos($href, 'https://') === 0) {
            $link['element']['attributes']['target'] = '_blank';
            return $link;
        }

        $targetFile = realpath($this->docsDir . '/docs/' . $href);

        if ($targetFile === false || strpos($targetFile, realpath($this->docsDir . '/docs/')) !== 0) {
            return [
                'extent' => $link['extent'],
                'element' => [
                    'name' => 'span',
                    'text' => $link['element']['text'],
                ],
            ];
        }

        $link['element']['attributes']['href'] = "/apps/docs/index.php?link=".urlencode($href);
        return $link;
    }
}

$docsDir = dirname(dirname(dirname(__DIR__)));
if(isset($_GET['link'])) {
    $link = $_GET['link'];
    $file = $docsDir.'/docs/' . $link;

    $realPath = realpath($file);
    if ($realPath === false || strpos($realPath, realpath($docsDir.'/docs/')) !== 0) {
        header("HTTP/1.0 404 Not Found");
        exit;
    }

} else {
    $file = $docsDir.'/docs/index.md';
}

$pd = new ParsedownExt($docsDir);
$pd->setSafeMode(true);
$text = file_get_contents($file);

define("PAGE", "Docs");
define("APP_NAME", "Docs");

?>
<?php
require_once __DIR__. '/../common/include/top.php';
?>

<ol class="breadcrumb m-0 ps-0 h4">
    <li class="breadcrumb-item"><a href="/apps/docs">Home</a></li>
</ol>
<?php echo $pd->text($text); ?>

<?php
require_once __DIR__ . '/../common/include/bottom.php';
?>
