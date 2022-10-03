<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\CommandHandling;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\ApiGuard\Consumer\Consumer;
use CultuurNet\UDB3\Broadway\CommandHandling\Validation\CompositeCommandValidator;
use CultuurNet\UDB3\Broadway\CommandHandling\Validation\ValidatingCommandBusDecorator;
use CultuurNet\UDB3\CommandHandling\AuthorizedCommandBus;
use CultuurNet\UDB3\CommandHandling\ResqueCommandBus;
use CultuurNet\UDB3\CommandHandling\SimpleContextAwareCommandBus;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebToken;
use CultuurNet\UDB3\Security\Permission\AnyOfVoter;
use CultuurNet\UDB3\Security\Permission\PermissionSwitchVoter;
use CultuurNet\UDB3\Security\PermissionVoterCommandBusSecurity;
use CultuurNet\UDB3\Security\LabelCommandBusSecurity;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\UserPermissionVoter;
use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use CultuurNet\UDB3\User\CurrentUser;

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
                return new LazyLoadingCommandBus(
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
            }
        );

        $container->addShared(
            'event_command_validator',
            function (): CompositeCommandValidator {
                return new CompositeCommandValidator();
            }
        );

        $container->addShared(
            'resque_command_bus_factory',
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
        );
    }
}
