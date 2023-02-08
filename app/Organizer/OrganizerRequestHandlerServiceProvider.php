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
use CultuurNet\UDB3\Http\Organizer\DeleteImageRequestHandler;
use CultuurNet\UDB3\Http\Organizer\DeleteLabelRequestHandler;
use CultuurNet\UDB3\Http\Organizer\DeleteOrganizerRequestHandler;
use CultuurNet\UDB3\Http\Organizer\GetOrganizerRequestHandler;
use CultuurNet\UDB3\Http\Organizer\GetPermissionsForCurrentUserRequestHandler;
use CultuurNet\UDB3\Http\Organizer\GetPermissionsForGivenUserRequestHandler;
use CultuurNet\UDB3\Http\Organizer\ImportOrganizerRequestHandler;
use CultuurNet\UDB3\Http\Organizer\LegacyOrganizerRequestBodyParser;
use CultuurNet\UDB3\Http\Organizer\UpdateContributorsRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateAddressRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateContactPointRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateDescriptionRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateImagesRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateMainImageRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateTitleRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateUrlRequestHandler;
use CultuurNet\UDB3\Http\Request\Body\CombinedRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\ImagesPropertyPolyfillRequestBodyParser;
use CultuurNet\UDB3\User\CurrentUser;

final class OrganizerRequestHandlerServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            ImportOrganizerRequestHandler::class,
            GetOrganizerRequestHandler::class,
            DeleteOrganizerRequestHandler::class,
            UpdateTitleRequestHandler::class,
            UpdateDescriptionRequestHandler::class,
            DeleteDescriptionRequestHandler::class,
            UpdateAddressRequestHandler::class,
            DeleteAddressRequestHandler::class,
            UpdateUrlRequestHandler::class,
            UpdateContactPointRequestHandler::class,
            AddImageRequestHandler::class,
            UpdateImagesRequestHandler::class,
            UpdateMainImageRequestHandler::class,
            DeleteImageRequestHandler::class,
            AddLabelRequestHandler::class,
            DeleteLabelRequestHandler::class,
            GetPermissionsForCurrentUserRequestHandler::class,
            UpdateContributorsRequestHandler::class,
            GetPermissionsForGivenUserRequestHandler::class,
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
                return new GetOrganizerRequestHandler($container->get('organizer_service'));
            }
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
            UpdateContributorsRequestHandler::class,
            fn () => new UpdateContributorsRequestHandler(
                $container->get('organizer_repository'),
                $container->get(ContributorRepository::class)
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
    }
}
