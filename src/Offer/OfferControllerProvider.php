<?php

namespace CultuurNet\UDB3\Silex\Offer;

use CultuurNet\UDB3\DescriptionJSONDeserializer;
use CultuurNet\UDB3\LabelJSONDeserializer;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Symfony\Deserializer\PriceInfo\PriceInfoJSONDeserializer;
use CultuurNet\UDB3\Symfony\Deserializer\TitleJSONDeserializer;
use CultuurNet\UDB3\Symfony\Offer\EditOfferRestController;
use CultuurNet\UDB3\Symfony\Offer\OfferPermissionController;
use CultuurNet\UDB3\Symfony\Offer\PatchOfferRestController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

class OfferControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $offerServices = [
            'event' => 'event_editor',
            'events' => 'event_editor',
            'place' => 'place_editing_service',
        ];

        foreach ($offerServices as $offerType => $serviceName) {
            $controllerName = "{$offerType}_offer_controller";
            $patchControllerName = "patch_{$offerType}_controller";
            $permissionControllerName = "permission_{$offerType}_controller";

            $app[$controllerName] = $app->share(
                function (Application $app) use ($serviceName) {
                    return new EditOfferRestController(
                        $app[$serviceName],
                        new LabelJSONDeserializer(),
                        new TitleJSONDeserializer(),
                        new DescriptionJSONDeserializer(),
                        new PriceInfoJSONDeserializer()
                    );
                }
            );

            $app[$patchControllerName] = $app->share(
                function (Application $app) use ($offerType) {
                    return new PatchOfferRestController(
                        OfferType::fromCaseInsensitiveValue($offerType),
                        $app['event_command_bus']
                    );
                }
            );

            $app[$permissionControllerName] = $app->share(
                function (Application $app) use ($offerType) {
                    $currentUserId = null;
                    if (!is_null($app['current_user'])) {
                        $currentUserId = new StringLiteral($app['current_user']->id);
                    }

                    return new OfferPermissionController(
                        Permission::AANBOD_BEWERKEN(),
                        $app['offer_permission_voter'],
                        $currentUserId
                    );
                }
            );

            /* @deprecated */
            $controllers
                ->post(
                    "{$offerType}/{cdbid}/labels",
                    "{$controllerName}:addLabel"
                );

            $controllers->put("{$offerType}/{cdbid}/labels/", "{$controllerName}:addLabel");

            $controllers
                ->delete(
                    "{$offerType}/{cdbid}/labels/{label}",
                    "{$controllerName}:removeLabel"
                );

            /* @deprecated */
            $controllers
                ->post(
                    "{$offerType}/{cdbid}/{lang}/title",
                    "{$controllerName}:translateTitle"
                );

            $controllers->put("{$offerType}/{cdbid}/{lang}/name", "{$controllerName}:translateTitle");

            /* @deprecated */
            $controllers
                ->post(
                    "{$offerType}/{cdbid}/{lang}/description",
                    "{$controllerName}:translateDescription"
                );

            $controllers->put("{$offerType}/{cdbid}/{lang}/description", "{$controllerName}:translateDescription");

            $controllers
                ->put(
                    "{$offerType}/{cdbid}/priceInfo",
                    "{$controllerName}:updatePriceInfo"
                );

            $controllers
                ->patch(
                    "{$offerType}/{cdbid}",
                    "{$patchControllerName}:handle"
                );

            /* @deprecated */
            $controllers
                ->get(
                    "{$offerType}/{offerId}/permission",
                    "{$permissionControllerName}:currentUserHasPermission"
                );

            $controllers->get("{$offerType}/{offerId}/permissions/", "{$permissionControllerName}:currentUserHasPermission");

            $controllers
                ->get(
                    "{$offerType}/{offerId}/permission/{userId}",
                    "{$permissionControllerName}:givenUserHasPermission"
                );
        }

        return $controllers;
    }
}
