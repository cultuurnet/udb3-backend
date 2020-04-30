<?php

namespace CultuurNet\UDB3\Silex\Variations;

use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Http\CommandDeserializerController;
use CultuurNet\UDB3\Variations\Command\CreateOfferVariationJSONDeserializer;
use CultuurNet\UDB3\Variations\Model\Properties\DefaultUrlValidator;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class VariationsControllerProvider implements ControllerProviderInterface
{
    /**
     * @inheritdoc
     */
    public function connect(Application $app)
    {
        $app['variations_write_controller'] = $app->share(
            function (Application $app) {
                $urlValidator = (new DefaultUrlValidator($app['iri_offer_identifier_factory']))
                    ->withEntityService(OfferType::EVENT(), $app['event_service'])
                    ->withEntityService(OfferType::PLACE(), $app['place_service']);

                $deserializer = new CreateOfferVariationJSONDeserializer();
                $deserializer->addUrlValidator(
                    $urlValidator
                );

                return new CommandDeserializerController(
                    $deserializer,
                    $app['event_command_bus']
                );
            }
        );

        /* @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->post('/', 'variations_write_controller:handle');

        return $controllers;
    }
}
