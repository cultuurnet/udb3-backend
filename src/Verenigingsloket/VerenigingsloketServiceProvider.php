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
            VerenigingsloketApiConnector::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            VerenigingsloketApiConnector::class,
            function () use ($container) {
                $config = $container->get('config');

                return new VerenigingsloketApiConnector(
                    new Client(['base_uri' => $config['verenigingsloket']['apiUrl']]),
                    $config['verenigingsloket']['websiteUrl'],
                    $config['verenigingsloket']['apiKey'],
                );
            }
        );
    }
}
