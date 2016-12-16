<?php

namespace CultuurNet\UDB3\Silex\Variations;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\HttpFoundation\Response\ApiProblemJsonResponse;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Symfony\CommandDeserializerController;
use CultuurNet\UDB3\Symfony\Variations\EditVariationsRestController;
use CultuurNet\UDB3\Symfony\Variations\ReadVariationsRestController;
use CultuurNet\UDB3\Variations\Command\CreateOfferVariationJSONDeserializer;
use CultuurNet\UDB3\Variations\Model\Properties\DefaultUrlValidator;
use Qandidate\Toggle\ToggleManager;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\Route;
use Symfony\Component\HttpFoundation\Response;

class VariationsControllerProvider implements ControllerProviderInterface
{
    /**
     * @inheritdoc
     */
    public function connect(Application $app)
    {
        $app['variations_read_controller'] = $app->share(
            function (Application $app) {
                return new ReadVariationsRestController(
                    $app['variations.jsonld_repository'],
                    $app['variations.search'],
                    $app['url_generator']
                );
            }
        );

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

        $app['variations_edit_controller'] = $app->share(
            function (Application $app) {
                return new EditVariationsRestController(
                    $app['variations.jsonld_repository'],
                    $app['event_command_bus']
                );
            }
        );

        /* @var ControllerCollection|Route $controllers */
        $controllers = $app['controllers_factory'];

        $controllers
            ->get('/', 'variations_read_controller:search')
            ->bind('variations');

        $controllers->post('/', 'variations_write_controller:handle');

        $controllers->get('/{id}', 'variations_read_controller:get');
        $controllers->patch('/{id}', 'variations_edit_controller:edit');
        $controllers->delete('/{id}', 'variations_edit_controller:delete');

        $toggles = $app['toggles'];

        // When the variations feature is turned off, return a 503
        // Service unavailable for all valid variation routes.
        if (!$toggles->active('variations', $app['toggles.context'])) {
            $serviceUnavailableController = function () {
                $problem = new ApiProblem('Feature is disabled on this installation.');
                $problem->setStatus(Response::HTTP_SERVICE_UNAVAILABLE);
                return new ApiProblemJsonResponse($problem);
            };

            $controllers->run($serviceUnavailableController);
        }

        return $controllers;
    }
}
