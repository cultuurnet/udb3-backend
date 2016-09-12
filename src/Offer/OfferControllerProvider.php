<?php

namespace CultuurNet\UDB3\Silex\Offer;

use CultuurNet\UDB3\DescriptionJSONDeserializer;
use CultuurNet\UDB3\LabelJSONDeserializer;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Symfony\Offer\EditOfferRestController;
use CultuurNet\UDB3\Symfony\Offer\PatchOfferRestController;
use CultuurNet\UDB3\TitleJSONDeserializer;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class OfferControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $offerServices = [
            'event' => 'event_editor_with_label_memory',
            'place' => 'place_editing_service_with_label_memory',
        ];

        foreach ($offerServices as $offerType => $serviceName) {
            $controllerName = "{$offerType}_offer_controller";
            $patchControllerName = "patch_{$offerType}_controller";

            $app[$controllerName] = $app->share(
                function (Application $app) use ($serviceName) {
                    return new EditOfferRestController(
                        $app[$serviceName],
                        new LabelJSONDeserializer(),
                        new TitleJSONDeserializer(),
                        new DescriptionJSONDeserializer()
                    );
                }
            );

            $app[$patchControllerName] = $app->share(
                function(Application $app) use ($offerType) {
                    return new PatchOfferRestController(
                        OfferType::fromCaseInsensitiveValue($offerType),
                        $app['event_command_bus']
                    );
                }
            );

            $controllers
                ->post(
                    "{$offerType}/{cdbid}/labels",
                    "{$controllerName}:addLabel"
                );

            $controllers
                ->delete(
                    "{$offerType}/{cdbid}/labels/{label}",
                    "{$controllerName}:removeLabel"
                );

            $controllers
                ->post(
                    "{$offerType}/{cdbid}/{lang}/title",
                    "{$controllerName}:translateTitle"
                );

            $controllers
                ->post(
                    "{$offerType}/{cdbid}/{lang}/description",
                    "{$controllerName}:translateDescription"
                );

            $controllers
                ->patch(
                    "{$offerType}/{offerId}",
                    "{$patchControllerName}:handle"
                );
        }

        return $controllers;
    }
}
