<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\CommandHandling;

use CultuurNet\UDB3\Broadway\CommandHandling\Validation\CompositeCommandValidator;
use CultuurNet\UDB3\Broadway\CommandHandling\Validation\ValidatingCommandBusDecorator;
use CultuurNet\UDB3\CommandHandling\AuthorizedCommandBus;
use CultuurNet\UDB3\CommandHandling\ResqueCommandBus;
use CultuurNet\UDB3\CommandHandling\SimpleContextAwareCommandBus;
use CultuurNet\UDB3\Security\Permission\AnyOfVoter;
use CultuurNet\UDB3\Security\Permission\PermissionSwitchVoter;
use CultuurNet\UDB3\Security\PermissionVoterCommandBusSecurity;
use CultuurNet\UDB3\Security\LabelCommandBusSecurity;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\UserPermissionVoter;
use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;

class CommandBusServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app['command_bus.security'] = $app->share(
            function ($app) {
                // Set up security to check permissions of AuthorizableCommand commands.
                $security = new PermissionVoterCommandBusSecurity(
                    $app['current_user_id'],
                    // Either allow everything for god users, or use a voter based on the specific permission
                    new AnyOfVoter(
                        $app['god_user_voter'],
                        (new PermissionSwitchVoter())
                            // Use the organizer voter for ORGANISATIES_BEWERKEN to take into account who is the owner
                            // and/or look at the constraint query in the role to only allow edits to a subset of
                            // organizers.
                            ->withVoter(
                                $app['organizer_permission_voter'],
                                Permission::organisatiesBewerken()
                            )
                            // Use the offer voter for AANBOD permissions to take into account who is the owner
                            // and/or look at the constraint query in the role to only allow edits to a subset of
                            // offers.
                            ->withVoter(
                                $app['offer_permission_voter'],
                                Permission::aanbodBewerken(),
                                Permission::aanbodModereren(),
                                Permission::aanbodVerwijderen()
                            )
                            // Other permissions should just be checked by seeing if the user has that permission.
                            ->withDefaultVoter(
                                new UserPermissionVoter(
                                    $app['user_permissions_read_repository']
                                )
                            )
                    )
                );

                // Set up security decorator to check if the current user can use the label(s) in an
                // AuthorizableLabelCommand (skipped otherwise).
                return new LabelCommandBusSecurity(
                    $security,
                    $app['current_user_id'],
                    $app[LabelServiceProvider::JSON_READ_REPOSITORY]
                );
            }
        );

        $app['authorized_command_bus'] = $app->share(
            function () use ($app) {
                return new AuthorizedCommandBus(
                    new SimpleContextAwareCommandBus(),
                    $app['current_user_id'],
                    $app['command_bus.security']
                );
            }
        );

        $app['event_command_bus'] = $app->share(
            function () use ($app) {
                return new LazyLoadingCommandBus(
                    new ValidatingCommandBusDecorator(
                        new ContextDecoratedCommandBus(
                            new RetryingCommandBus(
                                $app['authorized_command_bus']
                            ),
                            $app
                        ),
                        $app['event_command_validator']
                    )
                );
            }
        );

        $app['event_command_validator'] = $app->share(
            function () {
                return new CompositeCommandValidator();
            }
        );

        $app['resque_command_bus_factory'] = $app->protect(
            function ($queueName) use ($app) {
                $app[$queueName . '_command_bus_factory'] = function () use ($app, $queueName) {
                    $commandBus = new ResqueCommandBus(
                        $app['authorized_command_bus'],
                        $queueName,
                        $app['command_bus_event_dispatcher']
                    );

                    $commandBus->setLogger($app['logger_factory.resque_worker']($queueName));

                    return $commandBus;
                };

                $app[$queueName . '_command_validator'] = $app->share(
                    function () {
                        return new CompositeCommandValidator();
                    }
                );

                $app[$queueName . '_command_bus'] = $app->share(
                    function (Application $app) use ($queueName) {
                        return new ValidatingCommandBusDecorator(
                            new ContextDecoratedCommandBus(
                                $app[$queueName . '_command_bus_factory'],
                                $app
                            ),
                            $app[$queueName . '_command_validator']
                        );
                    }
                );

                $app[$queueName . '_command_bus_out'] = $app->share(
                    function (Application $app) use ($queueName) {
                        return $app[$queueName . '_command_bus_factory'];
                    }
                );
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}
