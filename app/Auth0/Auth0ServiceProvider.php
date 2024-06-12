<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Auth0;

use Auth0\SDK\API\Management;
use Auth0\SDK\Configuration\SdkConfiguration;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\User\Auth0\Auth0UserIdentityResolver;
use CultuurNet\UDB3\User\ManagementToken\ManagementTokenProvider;

final class Auth0ServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            Auth0UserIdentityResolver::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            Auth0UserIdentityResolver::class,
            function () use ($container): Auth0UserIdentityResolver {
                $config = new SdkConfiguration(null, SdkConfiguration::STRATEGY_NONE);
                $config->setDomain($container->get('config')['auth0']['domain']);
                $config->setManagementToken($container->get(ManagementTokenProvider::class)->token());
                return new Auth0UserIdentityResolver(new Management($config));
            }
        );
    }
}
