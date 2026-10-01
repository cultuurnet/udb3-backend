<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/web',
    ])
    ->withComposerBased(phpunit: true)
    ->withImportNames(importShortClasses: false)
    ->withRules([
        TypedPropertyFromAssignsRector::class,
        TypedPropertyFromStrictConstructorRector::class,
    ]);
