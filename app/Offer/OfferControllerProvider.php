<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Offer;

use CultuurNet\UDB3\DescriptionJSONDeserializer;
use CultuurNet\UDB3\Event\EventFacilityResolver;
use CultuurNet\UDB3\Http\Deserializer\PriceInfo\PriceInfoDataValidator;
use CultuurNet\UDB3\Http\Offer\AddVideoRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateBookingAvailabilityRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateStatusRequestHandler;
use CultuurNet\UDB3\LabelJSONDeserializer;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\PlaceFacilityResolver;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Http\Deserializer\Place\FacilitiesJSONDeserializer;
use CultuurNet\UDB3\Http\Deserializer\PriceInfo\PriceInfoJSONDeserializer;
use CultuurNet\UDB3\Http\Deserializer\TitleJSONDeserializer;
use CultuurNet\UDB3\Http\Offer\EditOfferRestController;
use CultuurNet\UDB3\Http\Offer\OfferPermissionController;
use CultuurNet\UDB3\Http\Offer\OfferPermissionsController;
use CultuurNet\UDB3\Http\Offer\PatchOfferRestController;
use Ramsey\Uuid\UuidFactory;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

class OfferControllerProvider implements ControllerProviderInterface, ServiceProviderInterface
{
    private string $offerType;

    public function __construct(OfferType $offerType)
    {
        $this->offerType = $offerType->toNative();
    }

    public function connect(Application $app): ControllerCollection
    {
        $controllerName = $this->getEditControllerName();
        $patchControllerName = $this->getPatchControllerName();
        $permissionsControllerName = $this->getPermissionsControllerName();
        $deprecatedPermissionControllerName = $this->getDeprecatedPermissionControllerName();

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->put('/{offerId}/status/', UpdateStatusRequestHandler::class);
        $controllers->put('/{offerId}/booking-availability/', UpdateBookingAvailabilityRequestHandler::class);

        $controllers->post('/{offerId}/videos/', AddVideoRequestHandler::class);

        $controllers->put('/{cdbid}/type/{typeId}/', "{$controllerName}:updateType");
        $controllers->put('/{cdbid}/theme/{themeId}/', "{$controllerName}:updateTheme");

        $controllers->put('/{cdbid}/facilities/', "{$controllerName}:updateFacilities");

        $controllers->delete('/{cdbid}/labels/{label}/', "{$controllerName}:removeLabel");
        $controllers->put('/{cdbid}/labels/{label}/', "{$controllerName}:addLabel");

        $controllers->put('/{cdbid}/name/{lang}/', "{$controllerName}:updateTitle");
        $controllers->put('/{cdbid}/description/{lang}/', "{$controllerName}:updateDescription");
        $controllers->put('/{cdbid}/price-info/', "{$controllerName}:updatePriceInfo");
        $controllers->patch('/{cdbid}/', "{$patchControllerName}:handle");
        $controllers->get('/{offerId}/permissions/', "{$permissionsControllerName}:getPermissionsForCurrentUser");
        $controllers->get('/{offerId}/permissions/{userId}/', "{$permissionsControllerName}:getPermissionsForGivenUser");

        /**
         * Legacy routes that we need to keep for backward compatibility.
         * These routes usually used an incorrect HTTP method.
         */
        $controllers->post('/{cdbid}/labels', "{$controllerName}:addLabelFromJsonBody");
        $controllers->post('/{cdbid}/{lang}/title', "{$controllerName}:updateTitle");
        $controllers->post('/{cdbid}/{lang}/description', "{$controllerName}:updateDescription");
        $controllers->post('/{cdbid}/facilities', "{$controllerName}:updateFacilitiesWithLabel");
        $controllers->get('/{offerId}/permission', "{$deprecatedPermissionControllerName}:currentUserHasPermission");
        $controllers->get('/{offerId}/permission/{userId}', "{$deprecatedPermissionControllerName}:givenUserHasPermission");

        return $controllers;
    }

    public function register(Application $app): void
    {
        $app[UpdateStatusRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateStatusRequestHandler($app['event_command_bus'])
        );

        $app[UpdateBookingAvailabilityRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateBookingAvailabilityRequestHandler($app['event_command_bus'])
        );

        $app[AddVideoRequestHandler::class] = $app->share(
            fn (Application $app) => new AddVideoRequestHandler($app['event_command_bus'], new UuidFactory())
        );

        $app[$this->getEditControllerName()] = $app->share(
            function (Application $app) {
                switch ($this->offerType) {
                    case 'Place':
                        $editor = $app['place_editing_service'];
                        $mainLanguageQuery = $app['place_main_language_query'];
                        $facilityResolver = new PlaceFacilityResolver();
                        break;
                    case 'Event':
                    default:
                        $editor = $app['event_editor'];
                        $mainLanguageQuery = $app['event_main_language_query'];
                        $facilityResolver = new EventFacilityResolver();
                }

                return new EditOfferRestController(
                    $app['event_command_bus'],
                    $editor,
                    $mainLanguageQuery,
                    new LabelJSONDeserializer(),
                    new TitleJSONDeserializer(false, new StringLiteral('name')),
                    new DescriptionJSONDeserializer(),
                    new PriceInfoJSONDeserializer(new PriceInfoDataValidator()),
                    new FacilitiesJSONDeserializer($facilityResolver)
                );
            }
        );

        $app[$this->getPatchControllerName()] = $app->share(
            function (Application $app) {
                return new PatchOfferRestController(
                    OfferType::fromCaseInsensitiveValue($this->offerType),
                    $app['event_command_bus']
                );
            }
        );

        $app[$this->getPermissionsControllerName()] = $app->share(
            function (Application $app) {
                $permissionsToCheck = [
                    Permission::AANBOD_BEWERKEN(),
                    Permission::AANBOD_MODEREREN(),
                    Permission::AANBOD_VERWIJDEREN(),
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
                    Permission::AANBOD_BEWERKEN(),
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
