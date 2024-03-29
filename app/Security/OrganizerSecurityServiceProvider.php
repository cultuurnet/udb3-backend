<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\ApiName;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Contributor\ContributorRepository;
use CultuurNet\UDB3\Security\Permission\AnyOfVoter;
use CultuurNet\UDB3\Security\Permission\ContributorVoter;
use CultuurNet\UDB3\Security\Permission\ResourceOwnerVoter;
use CultuurNet\UDB3\Security\Permission\Sapi3RoleConstraintVoter;
use GuzzleHttp\Psr7\Uri;
use Http\Adapter\Guzzle7\Client;

final class OrganizerSecurityServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'organizer_permission_voter',
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'organizer_permission_voter',
            fn () => new AnyOfVoter(
                $container->get('god_user_voter'),
                new ResourceOwnerVoter(
                    $container->get('organizer_owner.repository'),
                    $container->get(ApiName::class) !== ApiName::CLI
                ),
                new ContributorVoter(
                    $container->get(UserEmailAddressRepository::class),
                    $container->get(ContributorRepository::class)
                ),
                new Sapi3RoleConstraintVoter(
                    $container->get('user_constraints_read_repository'),
                    new Uri($container->get('config')['search']['v3']['base_url'] . '/organizers/'),
                    new Client(new \GuzzleHttp\Client()),
                    $container->get('config')['search']['v3']['api_key'] ?? null,
                    []
                )
            )
        );
    }
}
