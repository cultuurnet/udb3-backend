<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths(
        [
            __DIR__ . '/app',
            __DIR__ . '/src',
            __DIR__ . '/tests',
            __DIR__ . '/web',
        ]
    );

    $rectorConfig->sets([
        PHPUnitSetList::PHPUNIT_90,
    ]);

    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);
};
