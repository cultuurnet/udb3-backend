<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Organizer;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Http\Offer\OfferPermissionsController;
use CultuurNet\UDB3\Http\Organizer\EditOrganizerRestController;
use CultuurNet\UDB3\Http\Organizer\ReadOrganizerRestController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

class OrganizerControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
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

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->post('/', 'organizer_edit_controller:create');

        $controllers
            ->get('/{cdbid}', 'organizer_controller:get')
            ->bind('organizer');

        $controllers->delete('/{cdbid}', 'organizer_edit_controller:delete');

        $controllers->put(
            '/{organizerId}/url',
            'organizer_edit_controller:updateUrl'
        );

        $controllers->put(
            '/{organizerId}/name',
            'organizer_edit_controller:updateNameDeprecated'
        );

        $controllers->put(
            '/{organizerId}/name/{lang}',
            'organizer_edit_controller:updateName'
        );

        $controllers->put(
            '/{organizerId}/address',
            'organizer_edit_controller:updateAddressDeprecated'
        );

        $controllers->put(
            '/{organizerId}/address/{lang}',
            'organizer_edit_controller:updateAddress'
        );

        $controllers->delete(
            '/{organizerId}/address',
            'organizer_edit_controller:removeAddress'
        );

        $controllers->put(
            '/{organizerId}/contact-point',
            'organizer_edit_controller:updateContactPoint'
        );

        $controllers->put(
            '/{organizerId}/labels/{labelName}',
            'organizer_edit_controller:addLabel'
        );

        $controllers->delete(
            '{organizerId}/labels/{labelName}',
            'organizer_edit_controller:removeLabel'
        );

        $controllers->get('{offerId}/permissions/', 'organizer_permissions_controller:getPermissionsForCurrentUser');
        $controllers->get('{offerId}/permissions/{userId}', 'organizer_permissions_controller:getPermissionsForGivenUser');

        return $controllers;
    }
}
