<?php

namespace CultuurNet\UDB3\Silex\Offer;

use CultuurNet\UDB3\DescriptionJSONDeserializer;
use CultuurNet\UDB3\Event\EventFacilityResolver;
use CultuurNet\UDB3\LabelJSONDeserializer;
use CultuurNet\UDB3\Offer\OfferFacilityResolverInterface;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\PlaceFacilityResolver;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Http\Deserializer\Calendar\CalendarForEventDataValidator;
use CultuurNet\UDB3\Http\Deserializer\Calendar\CalendarForPlaceDataValidator;
use CultuurNet\UDB3\Http\Deserializer\Calendar\CalendarJSONDeserializer;
use CultuurNet\UDB3\Http\Deserializer\Calendar\CalendarJSONParser;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;
use CultuurNet\UDB3\Http\Deserializer\Place\FacilitiesJSONDeserializer;
use CultuurNet\UDB3\Http\Deserializer\PriceInfo\PriceInfoJSONDeserializer;
use CultuurNet\UDB3\Http\Deserializer\TitleJSONDeserializer;
use CultuurNet\UDB3\Http\Offer\EditOfferRestController;
use CultuurNet\UDB3\Http\Offer\OfferPermissionController;
use CultuurNet\UDB3\Http\Offer\OfferPermissionsController;
use CultuurNet\UDB3\Http\Offer\PatchOfferRestController;
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
            'event' => ['event_editor', 'event_main_language_query'],
            'events' => ['event_editor', 'event_main_language_query'],
            'place' => ['place_editing_service', 'place_main_language_query'],
            'places' => ['place_editing_service', 'place_main_language_query'],
        ];

        foreach ($offerServices as $offerType => $serviceNames) {
            $controllerName = "{$offerType}_offer_controller";
            $patchControllerName = "patch_{$offerType}_controller";
            $permissionsControllerName = "permissions_{$offerType}_controller";
            /** @deprecated */
            $permissionControllerName = "permission_{$offerType}_controller";

            $app[$controllerName] = $app->share(
                function (Application $app) use ($serviceNames, $offerType) {
                    return new EditOfferRestController(
                        $app[$serviceNames[0]],
                        $app[$serviceNames[1]],
                        new LabelJSONDeserializer(),
                        new TitleJSONDeserializer(false, new StringLiteral('name')),
                        new DescriptionJSONDeserializer(),
                        new PriceInfoJSONDeserializer(),
                        new CalendarJSONDeserializer(
                            new CalendarJSONParser(),
                            $this->getDataCalendarValidator($offerType)
                        ),
                        new FacilitiesJSONDeserializer(
                            $this->getFacilityResolver($offerType)
                        )
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

            $app[$permissionsControllerName] = $app->share(
                function (Application $app) {
                    $currentUserId = null;
                    if (!is_null($app['current_user'])) {
                        $currentUserId = new StringLiteral($app['current_user']->id);
                    }
                    $permissionsToCheck = array(
                        Permission::AANBOD_BEWERKEN(),
                        Permission::AANBOD_MODEREREN(),
                        Permission::AANBOD_VERWIJDEREN(),
                    );
                    return new OfferPermissionsController(
                        $permissionsToCheck,
                        $app['offer_permission_voter'],
                        $currentUserId
                    );
                }
            );

            /** @deprecated */
            $app[$permissionControllerName] = $app->share(
                function (Application $app) {
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

            $controllers->put("{$offerType}/{cdbid}/calendar", "{$controllerName}:updateCalendar");
            $controllers->put("{$offerType}/{cdbid}/type/{typeId}", "{$controllerName}:updateType");
            $controllers->put("{$offerType}/{cdbid}/theme/{themeId}", "{$controllerName}:updateTheme");
            $controllers->put("{$offerType}/{cdbid}/facilities/", "{$controllerName}:updateFacilities");

            $controllers->delete("{$offerType}/{cdbid}/labels/{label}", "{$controllerName}:removeLabel")
                ->assert('label', '.*');

            $controllers->put("{$offerType}/{cdbid}/labels/{label}", "{$controllerName}:addLabel");

            $controllers->put("{$offerType}/{cdbid}/name/{lang}", "{$controllerName}:updateTitle");
            $controllers->put("{$offerType}/{cdbid}/description/{lang}", "{$controllerName}:updateDescription");
            $controllers->put("{$offerType}/{cdbid}/priceInfo", "{$controllerName}:updatePriceInfo");
            $controllers->patch("{$offerType}/{cdbid}", "{$patchControllerName}:handle");
            $controllers->get("{$offerType}/{offerId}/permissions/", "{$permissionsControllerName}:getPermissionsForCurrentUser");
            $controllers->get("{$offerType}/{offerId}/permissions/{userId}", "{$permissionsControllerName}:getPermissionsForGivenUser");


            /* @deprecated */
            $controllers
                ->post(
                    "{$offerType}/{cdbid}/labels",
                    "{$controllerName}:addLabelFromJsonBody"
                );

            $controllers
                ->post(
                    "{$offerType}/{cdbid}/{lang}/title",
                    "{$controllerName}:updateTitle"
                );

            $controllers
                ->post(
                    "{$offerType}/{cdbid}/{lang}/description",
                    "{$controllerName}:updateDescription"
                );

            $controllers
                ->post(
                    "{$offerType}/{cdbid}/facilities",
                    "{$controllerName}:updateFacilitiesWithLabel"
                );

            $controllers
                ->get(
                    "{$offerType}/{offerId}/permission",
                    "{$permissionControllerName}:currentUserHasPermission"
                );

            $controllers
                ->get(
                    "{$offerType}/{offerId}/permission/{userId}",
                    "{$permissionControllerName}:givenUserHasPermission"
                );
        }

        return $controllers;
    }

    /**
     * @param string $offerType
     *
     * @return DataValidatorInterface
     */
    private function getDataCalendarValidator($offerType)
    {
        if (strpos($offerType, 'place') !== false) {
            return new CalendarForPlaceDataValidator();
        }

        return new CalendarForEventDataValidator();
    }

    /**
     * @param string $offerType
     * @return OfferFacilityResolverInterface
     */
    private function getFacilityResolver($offerType)
    {
        if (strpos($offerType, 'place') !== false) {
            return new PlaceFacilityResolver();
        }

        return new EventFacilityResolver();
    }
}
