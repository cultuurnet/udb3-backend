<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\CommandHandling;

use CultuurNet\UDB3\Broadway\CommandHandling\Validation\CompositeCommandValidator;
use CultuurNet\UDB3\Broadway\CommandHandling\Validation\ValidatingCommandBusDecorator;
use CultuurNet\UDB3\CommandHandling\AuthorizedCommandBus;
use CultuurNet\UDB3\CommandHandling\ResqueCommandBus;
use CultuurNet\UDB3\CommandHandling\SimpleContextAwareCommandBus;
use CultuurNet\UDB3\Security\Permission\CompositeVoter;
use CultuurNet\UDB3\Security\Permission\PermissionSplitVoter;
use CultuurNet\UDB3\Security\Permission\PermissionVoterInterface;
use CultuurNet\UDB3\Security\PermissionVoterSecurity;
use CultuurNet\UDB3\Offer\Security\SecurityWithLabelPrivacy;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\UserPermissionVoter;
use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

class CommandBusServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['command_bus.security'] = $app->share(
            function ($app) {
                $security = new PermissionVoterSecurity(
                    $app['current_user_id'],
                    new CompositeVoter(
                        $app['god_user_voter'],
                        (new PermissionSplitVoter())
                            ->withVoter(
                                $app['organizer_permission_voter_inner'],
                                Permission::ORGANISATIES_BEWERKEN()
                            )
                            ->withVoter(
                                $app['offer_permission_voter_inner'],
                                Permission::AANBOD_BEWERKEN(),
                                Permission::AANBOD_MODEREREN(),
                                Permission::AANBOD_VERWIJDEREN()
                            )
                            ->withVoter(
                                new UserPermissionVoter(
                                    $app['user_permissions_read_repository']
                                ),
                                Permission::VOORZIENINGEN_BEWERKEN(),
                                Permission::GEBRUIKERS_BEHEREN(),
                                Permission::LABELS_BEHEREN(),
                                Permission::ORGANISATIES_BEHEREN(),
                                Permission::PRODUCTIES_AANMAKEN(),
                                Permission::FILMS_AANMAKEN()
                            )
                            ->withVoter(
                                new class() implements PermissionVoterInterface {
                                    public function isAllowed(
                                        Permission $permission,
                                        StringLiteral $itemId,
                                        StringLiteral $userId
                                    ): bool {
                                        return true;
                                    }
                                },
                                Permission::MEDIA_UPLOADEN()
                            )
                    )
                );

                $security = new SecurityWithLabelPrivacy(
                    $security,
                    $app['current_user_id'],
                    $app[LabelServiceProvider::JSON_READ_REPOSITORY]
                );

                return $security;
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

    public function boot(Application $app)
    {
    }
}
