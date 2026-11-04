<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Verenigingsloket;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use GuzzleHttp\Client;

final class VerenigingsloketServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            VerenigingsloketConnector::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            VerenigingsloketConnector::class,
            function () use ($container) {
                $config = $container->get('config');

                // A single page can trigger two calls to this API, so a slow or unreachable
                // Verenigingsloket must fail fast instead of blocking the request for minutes.
                return new VerenigingsloketApiRepository(
                    new Client([
                        'base_uri' => $config['verenigingsloket']['apiUrl'],
                        'connect_timeout' => $config['verenigingsloket']['connect_timeout'] ?? 2,
                        'timeout' => $config['verenigingsloket']['timeout'] ?? 5,
                    ]),
                    $config['verenigingsloket']['websiteUrl'],
                    $config['verenigingsloket']['apiKey'],
                );
            }
        );
    }
}
