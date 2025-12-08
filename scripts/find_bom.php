<?php
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/..'));
$exts = ['php', 'twig', 'js', 'css', 'html', 'txt'];
foreach ($it as $file) {
    if (!$file->isFile()) continue;
    $path = $file->getPathname();
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    if (!in_array(strtolower($ext), $exts)) continue;
    $h = fopen($path, 'rb');
    if (!$h) continue;
    $b = fread($h, 3);
    fclose($h);
    if ($b === "\xEF\xBB\xBF") {
        echo $path . PHP_EOL;
    }
}

echo "Done\n";
