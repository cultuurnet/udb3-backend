<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Holidays;

use CultuurNet\UDB3\Cache\CacheFactory;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Http\Holidays\GetHolidaysRequestHandler;
use GuzzleHttp\Client;

final class HolidaysServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            HolidaysService::class,
            GetHolidaysRequestHandler::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            HolidaysService::class,
            fn () => new CachedHolidaysService(
                new OpenHolidaysApiService(new Client()),
                CacheFactory::create(
                    $container->get('app_cache'),
                    'holidays',
                    2592000
                )
            )
        );

        $container->addShared(
            GetHolidaysRequestHandler::class,
            fn () => new GetHolidaysRequestHandler(
                $container->get(HolidaysService::class),
                $container->get('clock')
            )
        );
    }
}
