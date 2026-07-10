<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Completeness;

final class CompletenessConfig
{
    public static function for(string $type): Weights
    {
        $config = require __DIR__ . '/../../config.completeness.php';

        return Weights::fromConfig($config[$type]);
    }
}
