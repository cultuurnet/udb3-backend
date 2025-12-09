<?php

declare(strict_types=1);

return Publiq\PhpCsFixer\Config::fromFolders(
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
        'bootstrap.php',
    ],
)->legacy();
