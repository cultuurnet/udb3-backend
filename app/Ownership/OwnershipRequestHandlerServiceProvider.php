<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Http\Ownership\ApproveOwnershipRequestHandler;
use CultuurNet\UDB3\Http\Ownership\DeleteOwnershipRequestHandler;
use CultuurNet\UDB3\Http\Ownership\GetOwnershipRequestHandler;
use CultuurNet\UDB3\Http\Ownership\OwnershipStatusGuard;
use CultuurNet\UDB3\Http\Ownership\RejectOwnershipRequestHandler;
use CultuurNet\UDB3\Http\Ownership\RequestOwnershipRequestHandler;
use CultuurNet\UDB3\Http\Ownership\SearchOwnershipRequestHandler;
use CultuurNet\UDB3\Http\Ownership\SuggestOwnershipsRequestHandler;
use CultuurNet\UDB3\Model\Organizer\OrganizerIDParser;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\OfferJsonDocumentReadRepository;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\Role\UserPermissionsServiceProvider;
use CultuurNet\UDB3\Search\OffersSapi3SearchService;
use CultuurNet\UDB3\User\CurrentUser;
use CultuurNet\UDB3\User\UserIdentityResolver;
use Ramsey\Uuid\UuidFactory;

final class OwnershipRequestHandlerServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            OwnershipStatusGuard::class,
            RequestOwnershipRequestHandler::class,
            GetOwnershipRequestHandler::class,
            SearchOwnershipRequestHandler::class,
            SuggestOwnershipsRequestHandler::class,
            ApproveOwnershipRequestHandler::class,
            RejectOwnershipRequestHandler::class,
            DeleteOwnershipRequestHandler::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            OwnershipStatusGuard::class,
            fn () => new OwnershipStatusGuard(
                $container->get(OwnershipSearchRepository::class),
                $container->get('organizer_permission_voter')
            )
        );

        $container->addShared(
            RequestOwnershipRequestHandler::class,
            fn () => new RequestOwnershipRequestHandler(
                $container->get('event_command_bus'),
                new UuidFactory(),
                $container->get(CurrentUser::class),
                $container->get(OwnershipSearchRepository::class),
                $container->get('organizer_jsonld_repository'),
                $container->get(OwnershipStatusGuard::class),
                $container->get(UserIdentityResolver::class)
            )
        );

        $container->addShared(
            GetOwnershipRequestHandler::class,
            fn () => new GetOwnershipRequestHandler(
                $container->get(OwnershipServiceProvider::OWNERSHIP_JSONLD_REPOSITORY)
            )
        );

        $container->addShared(
            SearchOwnershipRequestHandler::class,
            fn () => new SearchOwnershipRequestHandler(
                $container->get(OwnershipSearchRepository::class),
                $container->get(OwnershipServiceProvider::OWNERSHIP_JSONLD_REPOSITORY)
            )
        );

        $container->addShared(
            SuggestOwnershipsRequestHandler::class,
            fn () => new SuggestOwnershipsRequestHandler(
                $container->get(OffersSapi3SearchService::class),
                $container->get(OfferJsonDocumentReadRepository::class),
                $container->get(CurrentUser::class),
                $container->get(UserIdentityResolver::class),
                $container->get('organizer_jsonld_repository'),
                $container->get(OwnershipSearchRepository::class),
                $container->get(OrganizerIDParser::class),
                $container->get(UserPermissionsServiceProvider::USER_PERMISSIONS_READ_REPOSITORY)
            )
        );

        $container->addShared(
            ApproveOwnershipRequestHandler::class,
            fn () => new ApproveOwnershipRequestHandler(
                $container->get('event_command_bus'),
                $container->get(CurrentUser::class),
                $container->get(OwnershipStatusGuard::class)
            )
        );

        $container->addShared(
            RejectOwnershipRequestHandler::class,
            fn () => new RejectOwnershipRequestHandler(
                $container->get('event_command_bus'),
                $container->get(CurrentUser::class),
                $container->get(OwnershipStatusGuard::class)
            )
        );

        $container->addShared(
            DeleteOwnershipRequestHandler::class,
            fn () => new DeleteOwnershipRequestHandler(
                $container->get('event_command_bus'),
                $container->get(CurrentUser::class),
                $container->get(OwnershipStatusGuard::class)
            )
        );
    }
}
