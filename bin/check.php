<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$paths = ['src', 'config', 'templates', 'bin', 'tests', 'webroot'];
$count = 0;
$failed = false;
foreach ($paths as $path) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $path, FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }
        $command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname());
        exec($command, $output, $status);
        if ($status !== 0) {
            $failed = true;
            fwrite(STDERR, implode("\n", $output) . "\n");
        }
        $output = [];
        $count++;
    }
}
fwrite(STDOUT, "Sintaxis comprobada en {$count} archivos PHP.\n");
exit($failed ? 1 : 0);
