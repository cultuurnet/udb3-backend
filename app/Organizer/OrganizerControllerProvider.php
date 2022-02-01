<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Organizer;

use CultuurNet\UDB3\Http\Import\ImportLabelVisibilityRequestBodyParser;
use CultuurNet\UDB3\Http\Organizer\AddImageRequestHandler;
use CultuurNet\UDB3\Http\Organizer\AddLabelRequestHandler;
use CultuurNet\UDB3\Http\Organizer\DeleteAddressRequestHandler;
use CultuurNet\UDB3\Http\Organizer\DeleteDescriptionRequestHandler;
use CultuurNet\UDB3\Http\Organizer\DeleteImageRequestHandler;
use CultuurNet\UDB3\Http\Organizer\DeleteLabelRequestHandler;
use CultuurNet\UDB3\Http\Organizer\DeleteOrganizerRequestHandler;
use CultuurNet\UDB3\Http\Organizer\GetOrganizerRequestHandler;
use CultuurNet\UDB3\Http\Organizer\ImportOrganizerRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateAddressRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateContactPointRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateDescriptionRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateImagesRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateMainImageRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateTitleRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateUrlRequestHandler;
use CultuurNet\UDB3\Http\Request\Body\CombinedRequestBodyParser;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Http\Offer\OfferPermissionsController;
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

        $controllers->post('/', ImportOrganizerRequestHandler::class);
        $controllers->put('/{organizerId}/', ImportOrganizerRequestHandler::class);
        $controllers->get('/{organizerId}/', GetOrganizerRequestHandler::class)->bind('organizer');
        $controllers->delete('/{organizerId}/', DeleteOrganizerRequestHandler::class);

        $controllers->put('/{organizerId}/name/', UpdateTitleRequestHandler::class);
        $controllers->put('/{organizerId}/name/{language}/', UpdateTitleRequestHandler::class);

        $controllers->put('/{organizerId}/description/{language}/', UpdateDescriptionRequestHandler::class);
        $controllers->delete('/{organizerId}/description/{language}/', DeleteDescriptionRequestHandler::class);

        $controllers->put('/{organizerId}/address/', UpdateAddressRequestHandler::class);
        $controllers->put('/{organizerId}/address/{language}/', UpdateAddressRequestHandler::class);
        $controllers->delete('/{organizerId}/address/', DeleteAddressRequestHandler::class);

        $controllers->put('/{organizerId}/url/', UpdateUrlRequestHandler::class);

        $controllers->put('/{organizerId}/contact-point/', UpdateContactPointRequestHandler::class);

        $controllers->post('/{organizerId}/images/', AddImageRequestHandler::class);
        $controllers->put('/{organizerId}/images/main', UpdateMainImageRequestHandler::class);
        $controllers->patch('/{organizerId}/images/', UpdateImagesRequestHandler::class);
        $controllers->delete('/{organizerId}/images/{imageId}', DeleteImageRequestHandler::class);

        $controllers->put('/{organizerId}/labels/{labelName}/', AddLabelRequestHandler::class);
        $controllers->delete('/{organizerId}/labels/{labelName}/', DeleteLabelRequestHandler::class);

        $controllers->get('/{offerId}/permissions/', 'organizer_permissions_controller:getPermissionsForCurrentUser');
        $controllers->get('/{offerId}/permissions/{userId}/', 'organizer_permissions_controller:getPermissionsForGivenUser');

        return $controllers;
    }

    public function register(Application $app): void
    {
        $app[ImportOrganizerRequestHandler::class] = $app->share(
            fn (Application $app) => new ImportOrganizerRequestHandler(
                $app['organizer_repository'],
                $app['imports_command_bus'],
                $app['labels.labels_locked_for_import_repository'],
                $app['uuid_generator'],
                $app['organizer_iri_generator'],
                new CombinedRequestBodyParser(
                    new ImportLabelVisibilityRequestBodyParser(
                        $app[LabelServiceProvider::JSON_READ_REPOSITORY],
                        $app[LabelServiceProvider::RELATIONS_READ_REPOSITORY]
                    )
                )
            )
        );

        $app[GetOrganizerRequestHandler::class] = $app->share(
            fn (Application $application) => new GetOrganizerRequestHandler($app['organizer_service'])
        );

        $app[DeleteOrganizerRequestHandler::class] = $app->share(
            fn (Application $application) => new DeleteOrganizerRequestHandler($app['event_command_bus'])
        );

        $app[UpdateTitleRequestHandler::class] = $app->share(
            fn (Application $application) => new UpdateTitleRequestHandler($app['event_command_bus'])
        );

        $app[UpdateDescriptionRequestHandler::class] = $app->share(
            fn (Application $application) => new UpdateDescriptionRequestHandler($app['event_command_bus'])
        );

        $app[DeleteDescriptionRequestHandler::class] = $app->share(
            fn (Application $application) => new DeleteDescriptionRequestHandler($app['event_command_bus'])
        );

        $app[UpdateAddressRequestHandler::class] = $app->share(
            fn (Application $application) => new UpdateAddressRequestHandler($app['event_command_bus'])
        );

        $app[DeleteAddressRequestHandler::class] = $app->share(
            fn (Application $application) => new DeleteAddressRequestHandler($app['event_command_bus'])
        );

        $app[UpdateUrlRequestHandler::class] = $app->share(
            fn (Application $application) => new UpdateUrlRequestHandler($app['event_command_bus'])
        );

        $app[UpdateContactPointRequestHandler::class] = $app->share(
            fn (Application $application) => new UpdateContactPointRequestHandler($app['event_command_bus'])
        );

        $app[AddImageRequestHandler::class] = $app->share(
            fn (Application $application) => new AddImageRequestHandler(
                $app['event_command_bus'],
                $app['media_object_repository']
            )
        );

        $app[UpdateImagesRequestHandler::class] = $app->share(
            fn (Application $application) => new UpdateImagesRequestHandler($app['event_command_bus'])
        );

        $app[UpdateMainImageRequestHandler::class] = $app->share(
            fn (Application $application) => new UpdateMainImageRequestHandler($app['event_command_bus'])
        );

        $app[DeleteImageRequestHandler::class] = $app->share(
            fn (Application $application) => new DeleteImageRequestHandler($app['event_command_bus'])
        );

        $app[AddLabelRequestHandler::class] = $app->share(
            fn (Application $application) => new AddLabelRequestHandler($app['event_command_bus'])
        );

        $app[DeleteLabelRequestHandler::class] = $app->share(
            fn (Application $application) => new DeleteLabelRequestHandler($app['event_command_bus'])
        );

        $app['organizer_permissions_controller'] = $app->share(
            function (Application $app) {
                return new OfferPermissionsController(
                    [Permission::organisatiesBewerken()],
                    $app['organizer_permission_voter'],
                    $app['current_user_id'] ? new StringLiteral($app['current_user_id']) : null
                );
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}
