#!/usr/bin/env php
<?php
$sourceDir = '../apidocs/projects/uitdatabank/models';
$destinationDir = 'vendor/publiq/udb3-json-schemas';

function copyDirectory($src, $dst): void
{
    $dir = opendir($src);
//    @mkdir($dst);
    while (($file = readdir($dir)) !== false) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        if (is_dir($src . '/' . $file)) {
            copyDirectory($src . '/' . $file, $dst . '/' . $file);
        } else {
            copy($src . '/' . $file, $dst . '/' . $file);
        }
    }

    closedir($dir);
}

// Copy the directory
copyDirectory($sourceDir, $destinationDir);

echo "Copied json schema from apidocs!" . PHP_EOL;

