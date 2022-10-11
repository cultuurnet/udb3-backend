<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Culturefeed;

use CultureFeed;
use CultureFeed_DefaultOAuthClient;
use CultuurNet\UDB3\Container\AbstractServiceProvider;

final class CultureFeedServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'culturefeed',
            'uitpas',
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'culturefeed',
            function () use ($container): CultureFeed {
                $oauthClient = new CultureFeed_DefaultOAuthClient(
                    $container->get('config')['uitid']['consumer']['key'],
                    $container->get('config')['uitid']['consumer']['secret'],
                );
                $oauthClient->setEndpoint($container->get('config')['uitid']['base_url']);

                return new CultureFeed($oauthClient);
            }
        );

        $container->addShared(
            'uitpas',
            function () use ($container) {
                /** @var CultureFeed $cultureFeed */
                $cultureFeed = $container->get('culturefeed');
                return $cultureFeed->uitpas();
            }
        );
    }
}
