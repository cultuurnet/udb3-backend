<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Configuration;

use CultuurNet\UDB3\ApiName;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use League\Container\Argument\Literal\StringArgument;

final class ConfigurationServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            ApiName::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        if (!defined('API_NAME')) {
            define('API_NAME', ApiName::UNKNOWN);
        }
        $container->addShared(ApiName::class, new StringArgument(API_NAME));

    }
}
