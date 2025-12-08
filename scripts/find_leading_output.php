<?php
$paths = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/..'));
$exts = ['php','twig','html','htm'];
foreach ($it as $file) {
    if (!$file->isFile()) continue;
    $path = $file->getPathname();
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (!in_array($ext, $exts)) continue;
    $content = file_get_contents($path, false, null, 0, 512);
    if ($content === false) continue;
    // trim harmless UTF-8 BOM
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        echo "BOM: $path\n";
        continue;
    }
    // find first non-whitespace char
    $m = preg_match('/^(\s*)(.)/s', $content, $matches);
    if ($m) {
        $first = $matches[2];
        if ($ext === 'php') {
            if (!str_starts_with(ltrim($content), '<?php') && !str_starts_with(ltrim($content), '<?=')) {
                echo "Leading output before <?php: $path\n";
            }
        } else {
            // twig/html may start with HTML, ignore
        }
    }
}
echo "Done\n";
