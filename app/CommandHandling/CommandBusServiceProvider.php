<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\ApiGuard\Consumer\Consumer;
use CultuurNet\UDB3\Broadway\CommandHandling\Validation\CompositeCommandValidator;
use CultuurNet\UDB3\Broadway\CommandHandling\Validation\ValidatingCommandBusDecorator;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Event\CommandHandlers\CopyEventHandler;
use CultuurNet\UDB3\Event\CommandHandlers\DeleteOnlineUrlHandler;
use CultuurNet\UDB3\Event\CommandHandlers\RemoveThemeHandler;
use CultuurNet\UDB3\Event\CommandHandlers\UpdateAttendanceModeHandler;
use CultuurNet\UDB3\Event\CommandHandlers\UpdateAudienceHandler;
use CultuurNet\UDB3\Event\CommandHandlers\UpdateOnlineUrlHandler;
use CultuurNet\UDB3\Event\CommandHandlers\UpdateSubEventsHandler;
use CultuurNet\UDB3\Event\CommandHandlers\UpdateThemeHandler;
use CultuurNet\UDB3\Event\CommandHandlers\UpdateUiTPASPricesHandler;
use CultuurNet\UDB3\Event\EventCommandHandler;
use CultuurNet\UDB3\Event\Productions\ProductionCommandHandler;
use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebToken;
use CultuurNet\UDB3\Offer\CommandHandlers\AddLabelHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\AddVideoHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\ChangeOwnerHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\DeleteOfferHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\DeleteOrganizerHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\DeleteVideoHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\ImportLabelsHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\ImportVideosHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\RemoveLabelHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateAvailableFromHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateBookingAvailabilityHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateCalendarHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateFacilitiesHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateOrganizerHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdatePriceInfoHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateStatusHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateTitleHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateTypeHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateVideoHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\AddImageHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\DeleteDescriptionHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\ImportImagesHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\RemoveAddressHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\RemoveImageHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateAddressHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateContactPointHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateDescriptionHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateImageHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateMainImageHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateWebsiteHandler;
use CultuurNet\UDB3\Place\CommandHandler as PlaceCommandHandler;
use CultuurNet\UDB3\Role\CommandHandler as RoleCommandHandler;
use CultuurNet\UDB3\Security\Permission\AnyOfVoter;
use CultuurNet\UDB3\Security\Permission\PermissionSwitchVoter;
use CultuurNet\UDB3\Security\PermissionVoterCommandBusSecurity;
use CultuurNet\UDB3\Security\LabelCommandBusSecurity;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\UserPermissionVoter;
use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use CultuurNet\UDB3\User\CurrentUser;
use League\Container\Argument\Literal\ObjectArgument;

final class CommandBusServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'command_bus.security',
            'authorized_command_bus',
            'event_command_bus',
            'event_command_validator',
            'resque_command_bus_factory',
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
                            $container->get(CurrentUser::class)->getId(),
                            $container->get(JsonWebToken::class),
                            $container->get(ApiKey::class),
                            $container->get('api_name'),
                            $container->get(Consumer::class)
                        ),
                        $container->get('event_command_validator')
                    )
                );

                $commandBus->beforeFirstDispatch(
                    function (CommandBus $commandBus) use ($app) {
                        $commandBus->subscribe(
                            new EventCommandHandler(
                                $app['event_repository'],
                                $app['organizer_repository'],
                                $app['media_manager']
                            )
                        );

                        $commandBus->subscribe($app['saved_searches_command_handler']);

                        $commandBus->subscribe(
                            new PlaceCommandHandler(
                                $app['place_repository'],
                                $app['organizer_repository'],
                                $app['media_manager']
                            )
                        );

                        $commandBus->subscribe(
                            new RoleCommandHandler($app['real_role_repository'])
                        );

                        $commandBus->subscribe($app['media_manager']);
                        $commandBus->subscribe($app['place_geocoordinates_command_handler']);
                        $commandBus->subscribe($app['event_geocoordinates_command_handler']);
                        $commandBus->subscribe($app['organizer_geocoordinates_command_handler']);
                        $commandBus->subscribe($app[ProductionCommandHandler::class]);

                        // Offer command handlers
                        // @todo can we auto-discover these and register them automatically?
                        // @see https://jira.uitdatabank.be/browse/III-4176
                        $commandBus->subscribe($app[UpdateTitleHandler::class]);
                        $commandBus->subscribe($app[UpdateAvailableFromHandler::class]);
                        $commandBus->subscribe($app[UpdateCalendarHandler::class]);
                        $commandBus->subscribe($app[UpdateStatusHandler::class]);
                        $commandBus->subscribe($app[UpdateBookingAvailabilityHandler::class]);
                        $commandBus->subscribe($app[UpdateTypeHandler::class]);
                        $commandBus->subscribe($app[UpdateFacilitiesHandler::class]);
                        $commandBus->subscribe($app[ChangeOwnerHandler::class]);
                        $commandBus->subscribe($app[AddLabelHandler::class]);
                        $commandBus->subscribe($app[RemoveLabelHandler::class]);
                        $commandBus->subscribe($app[ImportLabelsHandler::class]);
                        $commandBus->subscribe($app[AddVideoHandler::class]);
                        $commandBus->subscribe($app[UpdateVideoHandler::class]);
                        $commandBus->subscribe($app[DeleteVideoHandler::class]);
                        $commandBus->subscribe($app[ImportVideosHandler::class]);
                        $commandBus->subscribe($app[DeleteOfferHandler::class]);
                        $commandBus->subscribe($app[UpdatePriceInfoHandler::class]);
                        $commandBus->subscribe($app[UpdateOrganizerHandler::class]);
                        $commandBus->subscribe($app[DeleteOrganizerHandler::class]);

                        // Event command handlers
                        $commandBus->subscribe($app[UpdateSubEventsHandler::class]);
                        $commandBus->subscribe($app[UpdateThemeHandler::class]);
                        $commandBus->subscribe($app[RemoveThemeHandler::class]);
                        $commandBus->subscribe($app[UpdateAttendanceModeHandler::class]);
                        $commandBus->subscribe($app[UpdateOnlineUrlHandler::class]);
                        $commandBus->subscribe($app[DeleteOnlineUrlHandler::class]);
                        $commandBus->subscribe($app[UpdateAudienceHandler::class]);
                        $commandBus->subscribe($app[UpdateUiTPASPricesHandler::class]);
                        $commandBus->subscribe($app[CopyEventHandler::class]);

                        // Organizer command handlers
                        $commandBus->subscribe($app[\CultuurNet\UDB3\Organizer\CommandHandler\DeleteOrganizerHandler::class]);
                        $commandBus->subscribe($app[\CultuurNet\UDB3\Organizer\CommandHandler\AddLabelHandler::class]);
                        $commandBus->subscribe($app[\CultuurNet\UDB3\Organizer\CommandHandler\RemoveLabelHandler::class]);
                        $commandBus->subscribe($app[\CultuurNet\UDB3\Organizer\CommandHandler\ImportLabelsHandler::class]);
                        $commandBus->subscribe($app[\CultuurNet\UDB3\Organizer\CommandHandler\UpdateTitleHandler::class]);
                        $commandBus->subscribe($app[UpdateDescriptionHandler::class]);
                        $commandBus->subscribe($app[DeleteDescriptionHandler::class]);
                        $commandBus->subscribe($app[UpdateAddressHandler::class]);
                        $commandBus->subscribe($app[RemoveAddressHandler::class]);
                        $commandBus->subscribe($app[UpdateWebsiteHandler::class]);
                        $commandBus->subscribe($app[UpdateContactPointHandler::class]);
                        $commandBus->subscribe($app[AddImageHandler::class]);
                        $commandBus->subscribe($app[UpdateMainImageHandler::class]);
                        $commandBus->subscribe($app[UpdateImageHandler::class]);
                        $commandBus->subscribe($app[RemoveImageHandler::class]);
                        $commandBus->subscribe($app[ImportImagesHandler::class]);
                        $commandBus->subscribe($app[\CultuurNet\UDB3\Organizer\CommandHandler\ChangeOwnerHandler::class]);

                        $commandBus->subscribe($app[LabelServiceProvider::COMMAND_HANDLER]);
                    }
                );

                return $commandBus;
            }
        );

        $container->addShared(
            'event_command_validator',
            function (): CompositeCommandValidator {
                return new CompositeCommandValidator();
            }
        );

        $container->add(
            'resque_command_bus_factory',
            new ObjectArgument(
                function ($queueName) use ($container) {
                $container->addShared(
                    $queueName . '_command_bus_factory',
                    function () use ($container, $queueName): ResqueCommandBus {
                        $commandBus = new ResqueCommandBus(
                            $container->get('authorized_command_bus'),
                            $queueName,
                            $container->get('command_bus_event_dispatcher')
                        );

                        $commandBus->setLogger($container->get('logger_factory.resque_worker')($queueName));

                        return $commandBus;
                    }
                );
                $container->addShared(
                    $queueName . '_command_validator',
                    function (): CompositeCommandValidator {
                        return new CompositeCommandValidator();
                    }
                );
            }
            )
        );
    }
}
