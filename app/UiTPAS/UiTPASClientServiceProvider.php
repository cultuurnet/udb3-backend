<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS;

use CultuurNet\UDB3\Cache\CacheFactory;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\UiTPAS\Client\RestUiTPASClient;
use CultuurNet\UDB3\UiTPAS\Client\UiTPASClient;
use CultuurNet\UDB3\User\Keycloak\KeycloakManagementTokenGenerator;
use CultuurNet\UDB3\User\ManagementToken\ManagementTokenProvider;
use GuzzleHttp\Client;

final class UiTPASClientServiceProvider extends AbstractServiceProvider
{
    public const TOKEN_PROVIDER = 'uitpas.rest_api.token_provider';

    protected function getProvidedServiceNames(): array
    {
        return [
            UiTPASClient::class,
            self::TOKEN_PROVIDER,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            self::TOKEN_PROVIDER,
            function () use ($container): ManagementTokenProvider {
                $config = $container->get('config')['uitpas']['rest_api'];

                return new ManagementTokenProvider(
                    new KeycloakManagementTokenGenerator(
                        new Client(),
                        rtrim($config['oauth_url'], '/'),
                        $config['realm'],
                        $config['client_id'],
                        $config['client_secret']
                    ),
                    CacheFactory::create($container->get('app_cache'), 'uitpas-rest-api', 0)
                );
            }
        );

        $container->addShared(
            UiTPASClient::class,
            function () use ($container): UiTPASClient {
                $config = $container->get('config')['uitpas']['rest_api'];
                return new RestUiTPASClient(
                    new Client(),
                    $container->get(self::TOKEN_PROVIDER),
                    $config['url'],
                    LoggerFactory::create($container, LoggerName::forService('uitpas', 'rest'))
                );
            }
        );
    }
}
