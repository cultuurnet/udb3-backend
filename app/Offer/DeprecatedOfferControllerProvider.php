<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Offer;

use CultuurNet\UDB3\DescriptionJSONDeserializer;
use CultuurNet\UDB3\Http\Offer\PatchOfferRequestHandler;
use CultuurNet\UDB3\LabelJSONDeserializer;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Http\Deserializer\TitleJSONDeserializer;
use CultuurNet\UDB3\Http\Offer\EditOfferRestController;
use CultuurNet\UDB3\Http\Offer\OfferPermissionController;
use CultuurNet\UDB3\Http\Offer\OfferPermissionsController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;
use CultuurNet\UDB3\StringLiteral;

/**
 * @deprecated
 *   Register RequestHandlerInterface implementations for offer routes in the new OfferControllerProvider.
 */
class DeprecatedOfferControllerProvider implements ControllerProviderInterface, ServiceProviderInterface
{
    private string $offerType;

    public function __construct(OfferType $offerType)
    {
        $this->offerType = $offerType->toString();
    }

    public function connect(Application $app): ControllerCollection
    {
        $controllerName = $this->getEditControllerName();
        $permissionsControllerName = $this->getPermissionsControllerName();
        $deprecatedPermissionControllerName = $this->getDeprecatedPermissionControllerName();

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->delete('/{cdbid}/labels/{label}/', "{$controllerName}:removeLabel");
        $controllers->put('/{cdbid}/labels/{label}/', "{$controllerName}:addLabel");

        $controllers->put('/{cdbid}/description/{lang}/', "{$controllerName}:updateDescription");
        $controllers->patch('/{offerId}/', PatchOfferRequestHandler::class);
        $controllers->get('/{offerId}/permissions/', "{$permissionsControllerName}:getPermissionsForCurrentUser");
        $controllers->get('/{offerId}/permissions/{userId}/', "{$permissionsControllerName}:getPermissionsForGivenUser");

        /**
         * Legacy routes that we need to keep for backward compatibility.
         * These routes usually used an incorrect HTTP method.
         */
        $controllers->post('/{cdbid}/labels/', "{$controllerName}:addLabelFromJsonBody");
        $controllers->post('/{cdbid}/{lang}/description/', "{$controllerName}:updateDescription");
        $controllers->get('/{offerId}/permission/', "{$deprecatedPermissionControllerName}:currentUserHasPermission");
        $controllers->get('/{offerId}/permission/{userId}/', "{$deprecatedPermissionControllerName}:givenUserHasPermission");

        return $controllers;
    }

    public function register(Application $app): void
    {
        $app[$this->getEditControllerName()] = $app->share(
            function (Application $app) {
                switch ($this->offerType) {
                    case 'Place':
                        $editor = $app['place_editing_service'];
                        $mainLanguageQuery = $app['place_main_language_query'];
                        break;
                    case 'Event':
                    default:
                        $editor = $app['event_editor'];
                        $mainLanguageQuery = $app['event_main_language_query'];
                }

                return new EditOfferRestController(
                    $app['event_command_bus'],
                    $editor,
                    $mainLanguageQuery,
                    new LabelJSONDeserializer(),
                    new TitleJSONDeserializer(false, new StringLiteral('name')),
                    new DescriptionJSONDeserializer()
                );
            }
        );

        $app[PatchOfferRequestHandler::class] = $app->share(
            fn (Application $app) => new PatchOfferRequestHandler($app['event_command_bus'])
        );

        $app[$this->getPermissionsControllerName()] = $app->share(
            function (Application $app) {
                $permissionsToCheck = [
                    Permission::aanbodBewerken(),
                    Permission::aanbodModereren(),
                    Permission::aanbodVerwijderen(),
                ];
                return new OfferPermissionsController(
                    $permissionsToCheck,
                    $app['offer_permission_voter'],
                    $app['current_user_id'] ? new StringLiteral($app['current_user_id']) : null
                );
            }
        );

        /* Only for legacy routes used for backward compatibility */
        $app[$this->getDeprecatedPermissionControllerName()] = $app->share(
            function (Application $app) {
                return new OfferPermissionController(
                    Permission::aanbodBewerken(),
                    $app['offer_permission_voter'],
                    $app['current_user_id'] ? new StringLiteral($app['current_user_id']) : null
                );
            }
        );
    }

    private function getEditControllerName(): string
    {
        return "{$this->offerType}_offer_controller";
    }

    private function getPatchControllerName(): string
    {
        return "patch_{$this->offerType}_controller";
    }

    private function getPermissionsControllerName(): string
    {
        return "permissions_{$this->offerType}_controller";
    }

    private function getDeprecatedPermissionControllerName(): string
    {
        return "permission_{$this->offerType}_controller";
    }

    public function boot(Application $app): void
    {
    }
}
