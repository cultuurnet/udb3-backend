<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Contributor\ContributorRepository;
use CultuurNet\UDB3\Http\Import\RemoveEmptyArraysRequestBodyParser;
use CultuurNet\UDB3\Http\Organizer\AddImageRequestHandler;
use CultuurNet\UDB3\Http\Organizer\AddLabelRequestHandler;
use CultuurNet\UDB3\Http\Organizer\DeleteAddressRequestHandler;
use CultuurNet\UDB3\Http\Organizer\DeleteDescriptionRequestHandler;
use CultuurNet\UDB3\Http\Organizer\DeleteEducationalDescriptionRequestHandler;
use CultuurNet\UDB3\Http\Organizer\DeleteImageRequestHandler;
use CultuurNet\UDB3\Http\Organizer\DeleteLabelRequestHandler;
use CultuurNet\UDB3\Http\Organizer\DeleteOrganizerRequestHandler;
use CultuurNet\UDB3\Http\Organizer\GetContributorsRequestHandler;
use CultuurNet\UDB3\Http\Organizer\GetCreatorRequestHandler;
use CultuurNet\UDB3\Http\Organizer\GetOrganizerRequestHandler;
use CultuurNet\UDB3\Http\Organizer\GetPermissionsForCurrentUserRequestHandler;
use CultuurNet\UDB3\Http\Organizer\GetPermissionsForGivenUserRequestHandler;
use CultuurNet\UDB3\Http\Organizer\GetVerenigingsloketRequestHandler;
use CultuurNet\UDB3\Http\Organizer\ImportOrganizerRequestHandler;
use CultuurNet\UDB3\Http\Organizer\LegacyOrganizerRequestBodyParser;
use CultuurNet\UDB3\Http\Organizer\UpdateAddressRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateContactPointRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateContributorsRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateDescriptionRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateEducationalDescriptionRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateImagesRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateLabelsRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateMainImageRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateTitleRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateUrlRequestHandler;
use CultuurNet\UDB3\Http\RDF\TurtleResponseFactory;
use CultuurNet\UDB3\Http\Request\Body\CombinedRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\ImagesPropertyPolyfillRequestBodyParser;
use CultuurNet\UDB3\Organizer\ReadModel\RDF\OrganizerJsonToTurtleConverter;
use CultuurNet\UDB3\User\CurrentUser;
use CultuurNet\UDB3\User\UserIdentityResolver;
use CultuurNet\UDB3\Verenigingsloket\VerenigingsloketApiConnector;

final class OrganizerRequestHandlerServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            ImportOrganizerRequestHandler::class,
            GetOrganizerRequestHandler::class,
            GetCreatorRequestHandler::class,
            DeleteOrganizerRequestHandler::class,
            UpdateTitleRequestHandler::class,
            UpdateDescriptionRequestHandler::class,
            DeleteDescriptionRequestHandler::class,
            UpdateEducationalDescriptionRequestHandler::class,
            DeleteEducationalDescriptionRequestHandler::class,
            UpdateAddressRequestHandler::class,
            DeleteAddressRequestHandler::class,
            UpdateUrlRequestHandler::class,
            UpdateContactPointRequestHandler::class,
            AddImageRequestHandler::class,
            UpdateImagesRequestHandler::class,
            UpdateMainImageRequestHandler::class,
            DeleteImageRequestHandler::class,
            AddLabelRequestHandler::class,
            UpdateLabelsRequestHandler::class,
            DeleteLabelRequestHandler::class,
            GetPermissionsForCurrentUserRequestHandler::class,
            GetContributorsRequestHandler::class,
            GetPermissionsForGivenUserRequestHandler::class,
            GetVerenigingsloketRequestHandler::class,
            UpdateContributorsRequestHandler::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            ImportOrganizerRequestHandler::class,
            function () use ($container) {
                return new ImportOrganizerRequestHandler(
                    $container->get('organizer_repository'),
                    $container->get('event_command_bus'),
                    new Version4Generator(),
                    $container->get('organizer_iri_generator'),
                    new CombinedRequestBodyParser(
                        new LegacyOrganizerRequestBodyParser(),
                        RemoveEmptyArraysRequestBodyParser::createForOrganizers(),
                        ImagesPropertyPolyfillRequestBodyParser::createForOrganizers(
                            $container->get('media_object_iri_generator'),
                            $container->get('media_object_repository')
                        )
                    )
                );
            }
        );

        $container->addShared(
            GetOrganizerRequestHandler::class,
            function () use ($container) {
                return new GetOrganizerRequestHandler(
                    $container->get('organizer_jsonld_repository'),
                    new TurtleResponseFactory(
                        $container->get(OrganizerJsonToTurtleConverter::class)
                    )
                );
            }
        );

        $container->addShared(
            GetCreatorRequestHandler::class,
            fn () => new GetCreatorRequestHandler(
                $container->get('organizer_jsonld_repository'),
                $container->get(UserIdentityResolver::class),
                $container->get('organizer_permission_voter'),
                $container->get(CurrentUser::class),
            )
        );

        $container->addShared(
            DeleteOrganizerRequestHandler::class,
            function () use ($container) {
                return new DeleteOrganizerRequestHandler($container->get('event_command_bus'));
            }
        );

        $container->addShared(
            UpdateTitleRequestHandler::class,
            function () use ($container) {
                return new UpdateTitleRequestHandler($container->get('event_command_bus'));
            }
        );

        $container->addShared(
            UpdateDescriptionRequestHandler::class,
            function () use ($container) {
                return new UpdateDescriptionRequestHandler($container->get('event_command_bus'));
            }
        );

        $container->addShared(
            UpdateEducationalDescriptionRequestHandler::class,
            function () use ($container) {
                return new UpdateEducationalDescriptionRequestHandler($container->get('event_command_bus'));
            }
        );

        $container->addShared(
            DeleteEducationalDescriptionRequestHandler::class,
            function () use ($container) {
                return new DeleteEducationalDescriptionRequestHandler($container->get('event_command_bus'));
            }
        );

        $container->addShared(
            DeleteDescriptionRequestHandler::class,
            function () use ($container) {
                return new DeleteDescriptionRequestHandler($container->get('event_command_bus'));
            }
        );

        $container->addShared(
            UpdateAddressRequestHandler::class,
            function () use ($container) {
                return new UpdateAddressRequestHandler($container->get('event_command_bus'));
            }
        );

        $container->addShared(
            DeleteAddressRequestHandler::class,
            function () use ($container) {
                return new DeleteAddressRequestHandler($container->get('event_command_bus'));
            }
        );

        $container->addShared(
            UpdateUrlRequestHandler::class,
            function () use ($container) {
                return new UpdateUrlRequestHandler($container->get('event_command_bus'));
            }
        );

        $container->addShared(
            UpdateContactPointRequestHandler::class,
            function () use ($container) {
                return new UpdateContactPointRequestHandler($container->get('event_command_bus'));
            }
        );

        $container->addShared(
            AddImageRequestHandler::class,
            function () use ($container) {
                return new AddImageRequestHandler(
                    $container->get('event_command_bus'),
                    $container->get('media_object_repository')
                );
            }
        );

        $container->addShared(
            UpdateImagesRequestHandler::class,
            function () use ($container) {
                return new UpdateImagesRequestHandler($container->get('event_command_bus'));
            }
        );

        $container->addShared(
            UpdateMainImageRequestHandler::class,
            function () use ($container) {
                return new UpdateMainImageRequestHandler($container->get('event_command_bus'));
            }
        );

        $container->addShared(
            DeleteImageRequestHandler::class,
            function () use ($container) {
                return new DeleteImageRequestHandler($container->get('event_command_bus'));
            }
        );

        $container->addShared(
            AddLabelRequestHandler::class,
            function () use ($container) {
                return new AddLabelRequestHandler($container->get('event_command_bus'));
            }
        );

        $container->addShared(
            UpdateLabelsRequestHandler::class,
            function () use ($container) {
                return new UpdateLabelsRequestHandler($container->get('event_command_bus'));
            }
        );

        $container->addShared(
            DeleteLabelRequestHandler::class,
            function () use ($container) {
                return new DeleteLabelRequestHandler($container->get('event_command_bus'));
            }
        );

        $container->addShared(
            GetPermissionsForCurrentUserRequestHandler::class,
            function () use ($container) {
                return new GetPermissionsForCurrentUserRequestHandler(
                    $container->get('organizer_permission_voter'),
                    $container->get(CurrentUser::class)->getId()
                );
            }
        );

        $container->addShared(
            GetContributorsRequestHandler::class,
            fn () => new GetContributorsRequestHandler(
                $container->get('organizer_repository'),
                $container->get(ContributorRepository::class),
                $container->get('organizer_permission_voter'),
                $container->get(CurrentUser::class)->getId()
            )
        );

        $container->addShared(
            GetPermissionsForGivenUserRequestHandler::class,
            function () use ($container) {
                return new GetPermissionsForGivenUserRequestHandler(
                    $container->get('organizer_permission_voter')
                );
            }
        );

        $container->addShared(
            GetVerenigingsloketRequestHandler::class,
            function () use ($container) {
                return new GetVerenigingsloketRequestHandler(
                    $container->get(VerenigingsloketApiConnector::class)
                );
            }
        );

        $container->addShared(
            UpdateContributorsRequestHandler::class,
            fn () => new UpdateContributorsRequestHandler($container->get('event_command_bus'))
        );
    }
}
