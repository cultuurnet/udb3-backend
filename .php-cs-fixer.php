<?php

declare(strict_types=1);

$config = Publiq\PhpCsFixer\Config::fromFolders(
    [
        'app/',
        'bin/',
        'src/',
        'tests/',
        'web/',
        'features/bootstrap/',
        'features/State/',
        'features/Steps/',
        'features/Support/',
    ],
)->legacy();

$config->getFinder()->append(['bootstrap.php']);

return $config;
