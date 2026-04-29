<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Holidays;

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
            fn () => new OpenHolidaysApiService(new Client(['timeout' => 5.0]))
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
