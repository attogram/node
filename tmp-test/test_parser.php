<?php
set_time_limit(5);
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__) . '/web/apps/docs/Parsedown.php';

class ParsedownExt extends Parsedown {
    private $docPath;
    private $baseDir;
    private $realBaseDir;

    public function __construct($docPath, $baseDir)
    {
        $this->docPath = $docPath;
        $this->baseDir = $baseDir;
        $this->realBaseDir = realpath($this->baseDir);
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

        $currentDocDir = $this->baseDir . ($this->docPath ? $this->docPath . '/' : '');
        $file = $currentDocDir . $href;

        $realFile = realpath($file);

        if ($realFile === false || strpos($realFile, $this->realBaseDir) !== 0) {
            $link['element']['attributes']['href'] = '#';
            if (isset($link['element']['attributes']['class'])) {
                $link['element']['attributes']['class'] .= ' broken-link';
            } else {
                $link['element']['attributes']['class'] = 'broken-link';
            }
            $link['element']['attributes']['title'] = 'Invalid link (points outside of documentation)';
            return $link;
        }

        if ($realFile == $this->realBaseDir) {
            $newDoc = '';
        } else {
            $newDoc = substr($realFile, strlen($this->realBaseDir) + 1);
            if (is_dir($realFile)) {
                $newDoc .= '/';
            }
        }

        $link['element']['attributes']['href'] = "/apps/docs/index.php?doc=".$newDoc;
        return $link;
    }
}

if ($argc < 2) {
    echo "Usage: php test_parser.php <markdown_file>\n";
    exit(1);
}

$file = $argv[1];

if (!file_exists($file)) {
    echo "Error: File not found: $file\n";
    exit(1);
}

$docsDir = dirname(__DIR__);
$baseDir = realpath($docsDir.'/docs/') . '/';
$realFile = realpath($file);

if (strpos($realFile, $baseDir) !== 0) {
    echo "Error: File is outside of the docs directory.\n";
    exit(1);
}

$relativePath = str_replace($baseDir, '', $realFile);
$docPath = dirname($relativePath);
if ($docPath === ".") {
    $docPath = "";
}

$text = file_get_contents($file);
$pd = new ParsedownExt($docPath, $baseDir);
$pd->setSafeMode(true);
echo $pd->text($text);
