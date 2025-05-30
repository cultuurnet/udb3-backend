<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Broadway\CommandHandling\Validation\CompositeCommandValidator;
use CultuurNet\UDB3\Broadway\CommandHandling\Validation\ValidatingCommandBusDecorator;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Event\EventCommandHandler;
use CultuurNet\UDB3\Event\Productions\ProductionCommandHandler;
use CultuurNet\UDB3\Labels\LabelServiceProvider;
use CultuurNet\UDB3\Log\SocketIOEmitterHandler;
use CultuurNet\UDB3\Mailer\Handler\SendOwnershipMailCommandHandler;
use CultuurNet\UDB3\Ownership\CommandHandlers\ApproveOwnershipHandler;
use CultuurNet\UDB3\Ownership\CommandHandlers\DeleteOwnershipHandler;
use CultuurNet\UDB3\Ownership\CommandHandlers\RejectOwnershipHandler;
use CultuurNet\UDB3\Ownership\CommandHandlers\RequestOwnershipHandler;
use CultuurNet\UDB3\Place\CommandHandler as PlaceCommandHandler;
use CultuurNet\UDB3\Place\ExtendedGeoCoordinatesCommandHandler;
use CultuurNet\UDB3\Role\CommandHandler as RoleCommandHandler;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\LabelCommandBusSecurity;
use CultuurNet\UDB3\Security\Permission\AnyOfVoter;
use CultuurNet\UDB3\Security\Permission\PermissionSwitchVoter;
use CultuurNet\UDB3\Security\Permission\UserPermissionVoter;
use CultuurNet\UDB3\Security\PermissionVoterCommandBusSecurity;
use CultuurNet\UDB3\User\CurrentUser;
use Monolog\Logger;
use Predis\Client;
use Psr\Container\ContainerInterface;
use Redis;
use SocketIO\Emitter;

final class CommandBusServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'command_bus.security',
            'authorized_command_bus',
            'event_command_bus',
            'event_export_command_bus',
            'event_export_command_bus_out',
            'bulk_label_offer_command_bus',
            'bulk_label_offer_command_bus_out',
            'logger_factory.resque_worker',

            'mails_command_bus',
            'mails_command_bus_out',
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'command_bus.security',
            function () use ($container): LabelCommandBusSecurity {
                // Set up security to check permissions of AuthorizableCommand commands.
                $security = new PermissionVoterCommandBusSecurity(
                    $container->get(CurrentUser::class)->getId(),
                    // Either allow everything for god users, or use a voter based on the specific permission
                    new AnyOfVoter(
                        $container->get('god_user_voter'),
                        (new PermissionSwitchVoter())
                            // Use the organizer voter for ORGANISATIES_BEWERKEN to take into account who is the owner
                            // and/or look at the constraint query in the role to only allow edits to a subset of
                            // organizers.
                            ->withVoter(
                                $container->get('organizer_permission_voter'),
                                Permission::organisatiesBewerken()
                            )
                            // Use the offer voter for AANBOD permissions to take into account who is the owner
                            // and/or look at the constraint query in the role to only allow edits to a subset of
                            // offers.
                            ->withVoter(
                                $container->get('offer_permission_voter'),
                                Permission::aanbodBewerken(),
                                Permission::aanbodModereren(),
                                Permission::aanbodVerwijderen()
                            )
                            // Other permissions should just be checked by seeing if the user has that permission.
                            ->withDefaultVoter(
                                new UserPermissionVoter(
                                    $container->get('user_permissions_read_repository')
                                )
                            )
                    )
                );

                // Set up security decorator to check if the current user can use the label(s) in an
                // AuthorizableLabelCommand (skipped otherwise).
                return new LabelCommandBusSecurity(
                    $security,
                    $container->get(CurrentUser::class)->getId(),
                    $container->get(LabelServiceProvider::JSON_READ_REPOSITORY)
                );
            }
        );

        $container->addShared(
            'authorized_command_bus',
            function () use ($container): AuthorizedCommandBus {
                return new AuthorizedCommandBus(
                    new SimpleContextAwareCommandBus(),
                    $container->get(CurrentUser::class)->getId(),
                    $container->get('command_bus.security')
                );
            }
        );

        $container->addShared(
            'event_command_bus',
            function () use ($container): LazyLoadingCommandBus {
                $commandBus = new LazyLoadingCommandBus(
                    new ValidatingCommandBusDecorator(
                        new ContextDecoratedCommandBus(
                            new RetryingCommandBus(
                                $container->get('authorized_command_bus')
                            ),
                            $container
                        ),
                        new CompositeCommandValidator()
                    )
                );

                $commandBus->beforeFirstDispatch(
                    function (CommandBus $commandBus) use ($container): void {
                        $commandBus->subscribe(
                            new EventCommandHandler(
                                $container->get('event_repository'),
                                $container->get('organizer_repository'),
                                $container->get('media_manager')
                            )
                        );

                        $commandBus->subscribe($container->get('saved_searches_command_handler'));

                        $commandBus->subscribe(
                            new PlaceCommandHandler(
                                $container->get('place_repository'),
                                $container->get('organizer_repository'),
                                $container->get('media_manager')
                            )
                        );

                        $commandBus->subscribe(
                            new RoleCommandHandler($container->get('real_role_repository'))
                        );

                        $commandBus->subscribe($container->get('media_manager'));
                        $commandBus->subscribe($container->get('place_geocoordinates_command_handler'));
                        $commandBus->subscribe($container->get('event_geocoordinates_command_handler'));
                        $commandBus->subscribe($container->get('organizer_geocoordinates_command_handler'));
                        $commandBus->subscribe($container->get(ExtendedGeoCoordinatesCommandHandler::class));
                        $commandBus->subscribe($container->get(ProductionCommandHandler::class));

                        // Offer command handlers
                        // @todo can we auto-discover these and register them automatically?
                        // @see https://jira.uitdatabank.be/browse/III-4176
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Offer\CommandHandlers\UpdateTitleHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Offer\CommandHandlers\UpdateAvailableFromHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Offer\CommandHandlers\UpdateCalendarHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Offer\CommandHandlers\UpdateStatusHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Offer\CommandHandlers\UpdateBookingAvailabilityHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Offer\CommandHandlers\UpdateTypeHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Offer\CommandHandlers\UpdateFacilitiesHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Offer\CommandHandlers\ChangeOwnerHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Offer\CommandHandlers\AddLabelHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Offer\CommandHandlers\RemoveLabelHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Offer\CommandHandlers\ImportLabelsHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Offer\CommandHandlers\ReplaceLabelsHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Offer\CommandHandlers\AddVideoHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Offer\CommandHandlers\UpdateVideoHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Offer\CommandHandlers\DeleteVideoHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Offer\CommandHandlers\ImportVideosHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Offer\CommandHandlers\DeleteOfferHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Offer\CommandHandlers\UpdatePriceInfoHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Offer\CommandHandlers\UpdateOrganizerHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Offer\CommandHandlers\DeleteOrganizerHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Offer\CommandHandlers\DeleteCurrentOrganizerHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Offer\CommandHandlers\UpdateContributorsHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Offer\CommandHandlers\DeleteDescriptionHandler::class));

                        // Event command handlers
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Event\CommandHandlers\UpdateSubEventsHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Event\CommandHandlers\UpdateThemeHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Event\CommandHandlers\RemoveThemeHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Event\CommandHandlers\UpdateAttendanceModeHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Event\CommandHandlers\UpdateOnlineUrlHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Event\CommandHandlers\DeleteOnlineUrlHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Event\CommandHandlers\UpdateAudienceHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Event\CommandHandlers\UpdateUiTPASPricesHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Event\CommandHandlers\CopyEventHandler::class));

                        // Organizer command handlers
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Organizer\CommandHandler\DeleteOrganizerHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Organizer\CommandHandler\AddLabelHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Organizer\CommandHandler\RemoveLabelHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Organizer\CommandHandler\ImportLabelsHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Organizer\CommandHandler\UpdateTitleHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Organizer\CommandHandler\UpdateDescriptionHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Organizer\CommandHandler\DeleteDescriptionHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Organizer\CommandHandler\UpdateEducationalDescriptionHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Organizer\CommandHandler\DeleteEducationalDescriptionHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Organizer\CommandHandler\UpdateAddressHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Organizer\CommandHandler\RemoveAddressHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Organizer\CommandHandler\UpdateWebsiteHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Organizer\CommandHandler\UpdateContactPointHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Organizer\CommandHandler\AddImageHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Organizer\CommandHandler\UpdateMainImageHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Organizer\CommandHandler\UpdateImageHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Organizer\CommandHandler\RemoveImageHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Organizer\CommandHandler\ImportImagesHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Organizer\CommandHandler\ChangeOwnerHandler::class));
                        $commandBus->subscribe($container->get(\CultuurNet\UDB3\Organizer\CommandHandler\UpdateContributorsHandler::class));

                        $commandBus->subscribe($container->get(RequestOwnershipHandler::class));
                        $commandBus->subscribe($container->get(ApproveOwnershipHandler::class));
                        $commandBus->subscribe($container->get(RejectOwnershipHandler::class));
                        $commandBus->subscribe($container->get(DeleteOwnershipHandler::class));

                        $commandBus->subscribe($container->get(LabelServiceProvider::COMMAND_HANDLER));
                    }
                );

                return $commandBus;
            }
        );

        $container->addShared(
            'event_export_command_bus',
            function () use ($container) {
                return new ValidatingCommandBusDecorator(
                    new ContextDecoratedCommandBus(
                        $this->createResqueCommandBus('event_export', $container),
                        $container
                    ),
                    new CompositeCommandValidator()
                );
            }
        );

        $container->addShared(
            'event_export_command_bus_out',
            function () use ($container) {
                $commandBus = $this->createResqueCommandBus('event_export', $container);
                $commandBus->subscribe($container->get('event_export_command_handler'));
                return $commandBus;
            }
        );

        $container->addShared(
            'mails_command_bus',
            function () {
                return new ContextDecoratedCommandBus(
                    $this->createResqueCommandBus('mails', $this->container),
                    $this->container
                );
            }
        );

        $container->addShared(
            'mails_command_bus_out',
            function () {
                $commandBus = $this->createResqueCommandBus('mails', $this->container);
                $commandBus->subscribe($this->container->get(SendOwnershipMailCommandHandler::class));
                return $commandBus;
            }
        );

        $container->addShared(
            'bulk_label_offer_command_bus',
            function () use ($container) {
                return new ValidatingCommandBusDecorator(
                    new ContextDecoratedCommandBus(
                        $this->createResqueCommandBus('bulk_label_offer', $container),
                        $container
                    ),
                    new CompositeCommandValidator()
                );
            }
        );

        $container->addShared(
            'bulk_label_offer_command_bus_out',
            function () use ($container) {
                $commandBus = $this->createResqueCommandBus('bulk_label_offer', $container);
                $commandBus->subscribe($container->get('bulk_label_offer_command_handler'));
                return $commandBus;
            }
        );

        $container->addShared(
            'logger_factory.resque_worker',
            new \League\Container\Argument\Literal\CallableArgument(
                function ($queueName) use ($container) {
                    $redisConfig = [
                        'host' => $container->get('config')['resque']['host'] ?? '127.0.0.1',
                        'port' => $container->get('config')['resque']['port'] ?? 6379,
                    ];
                    if (extension_loaded('redis')) {
                        $redis = new Redis();
                        $redis->connect(
                            $redisConfig['host'],
                            $redisConfig['port'],
                        );
                    } else {
                        $redis = new Client(
                            [
                                'host' => $redisConfig['host'],
                                'port' => $redisConfig['port'],
                            ]
                        );
                        $redis->connect();
                    }
                    $socketIOHandler = new SocketIOEmitterHandler(new Emitter($redis), Logger::INFO);

                    return LoggerFactory::create($container, LoggerName::forResqueWorker($queueName), [$socketIOHandler]);
                }
            )
        );
    }

    private function createResqueCommandBus(string $queueName, ContainerInterface $container): ResqueCommandBus
    {
        $commandBus = new ResqueCommandBus(
            $container->get('authorized_command_bus'),
            $queueName,
            $container->get('command_bus_event_dispatcher'),
            $container->get('config')['resque']['host'] ?? '127.0.0.1',
            $container->get('config')['resque']['port'] ?? 6379
        );
        $commandBus->setLogger($container->get('logger_factory.resque_worker')($queueName));
        return $commandBus;
    }
}
