<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Contributor\ContributorRepository;
use CultuurNet\UDB3\Event\EventOrganizerRelationService;
use CultuurNet\UDB3\Label\LabelImportPreProcessor;
use CultuurNet\UDB3\Labels\LabelServiceProvider;
use CultuurNet\UDB3\Organizer\CommandHandler\AddImageHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\AddLabelHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\ChangeOwnerHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\DeleteDescriptionHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\DeleteEducationalDescriptionHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\DeleteOrganizerHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\ImportImagesHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\ImportLabelsHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\RemoveAddressHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\RemoveImageHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\RemoveLabelHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\ReplaceLabelsHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateAddressHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateContactPointHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateContributorsHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateDescriptionHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateEducationalDescriptionHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateImageHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateMainImageHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateTitleHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateWebsiteHandler;
use CultuurNet\UDB3\Place\PlaceOrganizerRelationService;
use CultuurNet\UDB3\User\CurrentUser;

final class OrganizerCommandHandlerProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            DeleteOrganizerHandler::class,
            AddLabelHandler::class,
            RemoveLabelHandler::class,
            ImportLabelsHandler::class,
            ReplaceLabelsHandler::class,
            UpdateTitleHandler::class,
            UpdateDescriptionHandler::class,
            DeleteDescriptionHandler::class,
            UpdateEducationalDescriptionHandler::class,
            DeleteEducationalDescriptionHandler::class,
            UpdateAddressHandler::class,
            RemoveAddressHandler::class,
            UpdateWebsiteHandler::class,
            UpdateContactPointHandler::class,
            AddImageHandler::class,
            UpdateMainImageHandler::class,
            UpdateImageHandler::class,
            RemoveImageHandler::class,
            ImportImagesHandler::class,
            ChangeOwnerHandler::class,
            UpdateContributorsHandler::class,
        ];
    }
    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            DeleteOrganizerHandler::class,
            function () use ($container) {
                return new DeleteOrganizerHandler(
                    $container->get('organizer_repository'),
                    $container->get(EventOrganizerRelationService::class),
                    $container->get(PlaceOrganizerRelationService::class)
                );
            }
        );

        $container->addShared(
            AddLabelHandler::class,
            function () use ($container) {
                return new AddLabelHandler(
                    $container->get('organizer_repository'),
                    $container->get(LabelServiceProvider::JSON_READ_REPOSITORY),
                    $container->get('labels.constraint_aware_service')
                );
            }
        );

        $container->addShared(
            RemoveLabelHandler::class,
            function () use ($container) {
                return new RemoveLabelHandler($container->get('organizer_repository'));
            }
        );

        $container->addShared(
            ImportLabelsHandler::class,
            function () use ($container) {
                return new ImportLabelsHandler(
                    $container->get('organizer_repository'),
                    new LabelImportPreProcessor(
                        $container->get('labels.constraint_aware_service'),
                        $container->get(LabelServiceProvider::JSON_READ_REPOSITORY),
                        $container->get(CurrentUser::class)->getId()
                    )
                );
            }
        );

        $container->addShared(
            ReplaceLabelsHandler::class,
            function () use ($container) {
                return new ReplaceLabelsHandler(
                    $container->get('organizer_repository'),
                    new LabelImportPreProcessor(
                        $container->get('labels.constraint_aware_service'),
                        $container->get(LabelServiceProvider::JSON_READ_REPOSITORY),
                        $container->get(CurrentUser::class)->getId()
                    )
                );
            }
        );

        $container->addShared(
            UpdateTitleHandler::class,
            function () use ($container) {
                return new UpdateTitleHandler($container->get('organizer_repository'));
            }
        );

        $container->addShared(
            UpdateDescriptionHandler::class,
            function () use ($container) {
                return new UpdateDescriptionHandler($container->get('organizer_repository'));
            }
        );

        $container->addShared(
            DeleteDescriptionHandler::class,
            function () use ($container) {
                return new DeleteDescriptionHandler($container->get('organizer_repository'));
            }
        );

        $container->addShared(
            UpdateEducationalDescriptionHandler::class,
            function () use ($container) {
                return new UpdateEducationalDescriptionHandler($container->get('organizer_repository'));
            }
        );

        $container->addShared(
            DeleteEducationalDescriptionHandler::class,
            function () use ($container) {
                return new DeleteEducationalDescriptionHandler($container->get('organizer_repository'));
            }
        );

        $container->addShared(
            UpdateAddressHandler::class,
            function () use ($container) {
                return new UpdateAddressHandler($container->get('organizer_repository'));
            }
        );

        $container->addShared(
            RemoveAddressHandler::class,
            function () use ($container) {
                return new RemoveAddressHandler($container->get('organizer_repository'));
            }
        );

        $container->addShared(
            UpdateWebsiteHandler::class,
            function () use ($container) {
                return new UpdateWebsiteHandler($container->get('organizer_repository'));
            }
        );

        $container->addShared(
            UpdateContactPointHandler::class,
            function () use ($container) {
                return new UpdateContactPointHandler($container->get('organizer_repository'));
            }
        );

        $container->addShared(
            AddImageHandler::class,
            function () use ($container) {
                return new AddImageHandler($container->get('organizer_repository'));
            }
        );

        $container->addShared(
            UpdateMainImageHandler::class,
            function () use ($container) {
                return new UpdateMainImageHandler($container->get('organizer_repository'));
            }
        );

        $container->addShared(
            UpdateImageHandler::class,
            function () use ($container) {
                return new UpdateImageHandler($container->get('organizer_repository'));
            }
        );

        $container->addShared(
            RemoveImageHandler::class,
            function () use ($container) {
                return new RemoveImageHandler($container->get('organizer_repository'));
            }
        );

        $container->addShared(
            ImportImagesHandler::class,
            function () use ($container) {
                return new ImportImagesHandler($container->get('organizer_repository'));
            }
        );

        $container->addShared(
            ChangeOwnerHandler::class,
            function () use ($container) {
                return new ChangeOwnerHandler($container->get('organizer_repository'));
            }
        );

        $container->addShared(
            UpdateContributorsHandler::class,
            fn () => new UpdateContributorsHandler(
                $container->get('organizer_repository'),
                $container->get(ContributorRepository::class)
            )
        );
    }
}
