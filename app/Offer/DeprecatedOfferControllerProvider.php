<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Offer;

use CultuurNet\UDB3\Offer\OfferType;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

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

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];


        /**
         * Legacy routes that we need to keep for backward compatibility.
         * These routes usually used an incorrect HTTP method.
         */


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
            }
        );
    }

    private function getEditControllerName(): string
    {
        return "{$this->offerType}_offer_controller";
    }

    public function boot(Application $app): void
    {
    }
}
