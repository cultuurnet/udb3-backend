<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Database;

use CultuurNet\UDB3\Container\AbstractServiceProvider;

final class DatabaseServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [

        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();
    }
}
