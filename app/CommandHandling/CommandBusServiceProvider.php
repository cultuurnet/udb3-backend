<?php

namespace CultuurNet\UDB3\Silex\CommandHandling;

use CultuurNet\Broadway\CommandHandling\Validation\CompositeCommandValidator;
use CultuurNet\Broadway\CommandHandling\Validation\ValidatingCommandBusDecorator;
use CultuurNet\UDB3\CommandHandling\AuthorizedCommandBus;
use CultuurNet\UDB3\CommandHandling\ResqueCommandBus;
use CultuurNet\UDB3\CommandHandling\SimpleContextAwareCommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateFacilities as EventUpdateFacilities;
use CultuurNet\UDB3\Media\MediaSecurity;
use CultuurNet\UDB3\Offer\Security\Permission\CompositeVoter;
use CultuurNet\UDB3\Offer\Security\Permission\PermissionSplitVoter;
use CultuurNet\UDB3\Offer\Security\Security;
use CultuurNet\UDB3\Offer\Security\SecurityWithLabelPrivacy;
use CultuurNet\UDB3\Place\Commands\UpdateFacilities as PlaceUpdateFacilities;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\ClassNameCommandFilter;
use CultuurNet\UDB3\Security\CultureFeedUserIdentification;
use CultuurNet\UDB3\Security\SecurityWithUserPermission;
use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use Qandidate\Toggle\ToggleManager;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

class CommandBusServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        // This voter delegates to another voter based on which permission to
        // check. It covers detailed permissions for offer and organizers.
        $app['command_bus.split_permission_voter'] = $app->share(
            function (Application $app) {
                $splitter = (new PermissionSplitVoter())
                    ->withVoter(
                        $app['organizer_permission_voter_inner'],
                        Permission::ORGANISATIES_BEWERKEN()
                    )
                    ->withVoter(
                        $app['offer_permission_voter_inner'],
                        Permission::AANBOD_BEWERKEN(),
                        Permission::AANBOD_MODEREREN(),
                        Permission::AANBOD_VERWIJDEREN()
                    );

                return $splitter;
            }
        );

        $app['command_bus.security'] = $app->share(
            function ($app) {
                $security = new Security(
                    $app['current_user_identification'],
                    new CompositeVoter(
                        $app['god_user_voter'],
                        $app['command_bus.split_permission_voter']
                    )
                );

                $security = new SecurityWithLabelPrivacy(
                    $security,
                    $app['current_user_identification'],
                    $app[LabelServiceProvider::JSON_READ_REPOSITORY]
                );

                $security = new MediaSecurity($security);

                $security = new SecurityWithUserPermission(
                    $security,
                    $app['current_user_identification'],
                    $app['facility_permission_voter'],
                    new ClassNameCommandFilter(
                        new StringLiteral(PlaceUpdateFacilities::class),
                        new StringLiteral(EventUpdateFacilities::class)
                    )
                );

                return $security;
            }
        );

        $app['authorized_command_bus'] = $app->share(
            function () use ($app) {
                return new AuthorizedCommandBus(
                    new SimpleContextAwareCommandBus(),
                    new CultureFeedUserIdentification(
                        $app['current_user'],
                        $app['config']['user_permissions']
                    ),
                    $app['command_bus.security']
                );
            }
        );

        $app['event_command_bus'] = $app->share(
            function () use ($app) {
                return new LazyLoadingCommandBus(
                    new ValidatingCommandBusDecorator(
                        new ContextDecoratedCommandBus(
                            $app['authorized_command_bus'],
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

                    $commandBus->setLogger($app['logger.command_bus']);

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
