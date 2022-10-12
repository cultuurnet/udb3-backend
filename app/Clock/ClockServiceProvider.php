<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Clock;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use DateTimeZone;

final class ClockServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'timezone',
            'clock',
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'timezone',
            fn () => new DateTimeZone(
                empty($container->get('config')['timezone']) ? 'Europe/Brussels' : $container->get('config')['timezone']
            )
        );

        $container->addShared(
            'clock',
            fn () => new SystemClock($container->get('timezone'))
        );
    }
}
