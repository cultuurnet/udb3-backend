<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Organizer;

use CultuurNet\UDB3\Http\Organizer\UpdateAddressRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateTitleRequestHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\AddLabelHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\ImportLabelsHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\RemoveLabelHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateAddressHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateTitleHandler;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Http\Offer\OfferPermissionsController;
use CultuurNet\UDB3\Http\Organizer\EditOrganizerRestController;
use CultuurNet\UDB3\Http\Organizer\ReadOrganizerRestController;
use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

class OrganizerControllerProvider implements ControllerProviderInterface, ServiceProviderInterface
{
    public function connect(Application $app): ControllerCollection
    {
        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->post('/', 'organizer_edit_controller:create');

        $controllers
            ->get('/{cdbid}/', 'organizer_controller:get')
            ->bind('organizer');

        $controllers->delete('/{cdbid}/', 'organizer_edit_controller:delete');

        $controllers->put(
            '/{organizerId}/url/',
            'organizer_edit_controller:updateUrl'
        );

        $controllers->put('/{organizerId}/name/', UpdateTitleRequestHandler::class);
        $controllers->put('/{organizerId}/name/{language}/', UpdateTitleRequestHandler::class);

        $controllers->put('/{organizerId}/address/', UpdateAddressRequestHandler::class);
        $controllers->put('/{organizerId}/address/{language}/', UpdateAddressRequestHandler::class);

        $controllers->delete(
            '/{organizerId}/address/',
            'organizer_edit_controller:removeAddress'
        );

        $controllers->put(
            '/{organizerId}/contact-point/',
            'organizer_edit_controller:updateContactPoint'
        );

        $controllers->put(
            '/{organizerId}/labels/{labelName}/',
            'organizer_edit_controller:addLabel'
        );

        $controllers->delete(
            '/{organizerId}/labels/{labelName}/',
            'organizer_edit_controller:removeLabel'
        );

        $controllers->get('/{offerId}/permissions/', 'organizer_permissions_controller:getPermissionsForCurrentUser');
        $controllers->get('/{offerId}/permissions/{userId}/', 'organizer_permissions_controller:getPermissionsForGivenUser');

        return $controllers;
    }

    public function register(Application $app): void
    {
        $app['organizer_controller'] = $app->share(
            function (Application $app) {
                return new ReadOrganizerRestController(
                    $app['organizer_service']
                );
            }
        );

        $app['organizer_edit_controller'] = $app->share(
            function (Application $app) {
                return new EditOrganizerRestController(
                    $app['event_command_bus'],
                    $app['organizer_editing_service'],
                    $app['organizer_iri_generator']
                );
            }
        );

        $app['organizer_permissions_controller'] = $app->share(
            function (Application $app) {
                return new OfferPermissionsController(
                    [Permission::ORGANISATIES_BEWERKEN()],
                    $app['organizer_permission_voter'],
                    $app['current_user_id'] ? new StringLiteral($app['current_user_id']) : null
                );
            }
        );

        $app[AddLabelHandler::class] = $app->share(
            function (Application $app) {
                return new AddLabelHandler(
                    $app['organizer_repository'],
                    $app[LabelServiceProvider::JSON_READ_REPOSITORY],
                    $app['labels.constraint_aware_service']
                );
            }
        );

        $app[RemoveLabelHandler::class] = $app->share(
            function (Application $app) {
                return new RemoveLabelHandler(
                    $app['organizer_repository'],
                    $app[LabelServiceProvider::JSON_READ_REPOSITORY]
                );
            }
        );

        $app[ImportLabelsHandler::class] = $app->share(
            function (Application $app) {
                return new ImportLabelsHandler(
                    $app['organizer_repository'],
                    $app['labels.constraint_aware_service']
                );
            }
        );

        $app[UpdateTitleRequestHandler::class] = $app->share(
            fn (Application $application) => new UpdateTitleRequestHandler($app['event_command_bus'])
        );

        $app[UpdateAddressRequestHandler::class] = $app->share(
            fn (Application $application) => new UpdateAddressRequestHandler($app['event_command_bus'])
        );

        $app[UpdateTitleHandler::class] = $app->share(
            fn (Application $application) => new UpdateTitleHandler($app['organizer_repository'])
        );

        $app[UpdateAddressHandler::class] = $app->share(
            fn (Application $application) => new UpdateAddressHandler($app['organizer_repository'])
        );
    }

    public function boot(Application $app): void
    {
    }
}
