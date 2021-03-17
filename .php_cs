<?php

declare(strict_types=1);

return Publiq\PhpCsFixer\Config::fromFolders(
    [
        'app/',
        'bin/',
        'src/',
        'tests/',
    ]
)->legacy();
