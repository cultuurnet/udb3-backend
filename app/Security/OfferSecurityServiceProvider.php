<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\ApiName;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Contributor\ContributorRepository;
use CultuurNet\UDB3\Event\EventPermissionServiceProvider;
use CultuurNet\UDB3\Security\Permission\AnyOfVoter;
use CultuurNet\UDB3\Security\Permission\ContributorVoter;
use CultuurNet\UDB3\Security\Permission\DeleteUiTPASPlaceVoter;
use CultuurNet\UDB3\Security\Permission\ResourceOwnerVoter;
use CultuurNet\UDB3\Security\Permission\Sapi3RoleConstraintVoter;
use CultuurNet\UDB3\Security\ResourceOwner\CombinedResourceOwnerQuery;
use GuzzleHttp\Psr7\Uri;
use Http\Adapter\Guzzle7\Client;

final class OfferSecurityServiceProvider extends AbstractServiceProvider
{
    public const OFFER_CREATOR_QUERY = 'offer_creator_query';

    protected function getProvidedServiceNames(): array
    {
        return [
            self::OFFER_CREATOR_QUERY,
            'offer_permission_voter',
            DeleteUiTPASPlaceVoter::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            self::OFFER_CREATOR_QUERY,
            fn () => new CombinedResourceOwnerQuery([
                $container->get(EventPermissionServiceProvider::EVENT_OWNER_REPOSITORY),
                $container->get(EventPermissionServiceProvider::EVENT_ORGANIZER_OWNER_REPOSITORY),
                $container->get('place_owner.repository'),
            ])
        );

        $container->addShared(
            'offer_permission_voter',
            fn () => new AnyOfVoter(
                $container->get('god_user_voter'),
                new ResourceOwnerVoter(
                    $container->get(self::OFFER_CREATOR_QUERY),
                    $container->get(ApiName::class) !== ApiName::CLI
                ),
                new ContributorVoter(
                    $container->get(UserEmailAddressRepository::class),
                    $container->get(ContributorRepository::class)
                ),
                new Sapi3RoleConstraintVoter(
                    $container->get('user_constraints_read_repository'),
                    new Uri($container->get('config')['search']['v3']['base_url'] . '/offers/'),
                    new Client(new \GuzzleHttp\Client()),
                    $container->get('config')['search']['v3']['api_key'] ?? null,
                    ['disableDefaultFilters' => true]
                )
            )
        );


        $container->addShared(
            DeleteUiTPASPlaceVoter::class,
            fn () => new DeleteUiTPASPlaceVoter(
                $container->get('place_jsonld_repository'),
                $container->get('config')['uitpas']['labels']
            )
        );
    }
}
