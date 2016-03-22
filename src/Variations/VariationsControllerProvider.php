<?php

namespace CultuurNet\UDB3\Silex\Variations;

use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\Hydra\PagedCollection;
use CultuurNet\Hydra\Symfony\PageUrlGenerator;
use CultuurNet\UDB3\Symfony\CommandDeserializerController;
use CultuurNet\UDB3\Symfony\JsonLdResponse;
use CultuurNet\UDB3\Symfony\Variations\EditVariationsRestController;
use CultuurNet\UDB3\Symfony\Variations\ReadVariationsRestController;
use CultuurNet\UDB3\Variations\Command\CreateEventVariationJSONDeserializer;
use CultuurNet\UDB3\Variations\Command\CreateOfferVariationJSONDeserializer;
use CultuurNet\UDB3\Variations\Command\DeleteEventVariation;
use CultuurNet\UDB3\Variations\Command\EditDescriptionJSONDeserializer;
use CultuurNet\UDB3\Variations\Model\Properties\DefaultUrlValidator;
use CultuurNet\UDB3\Variations\Model\Properties\Id;
use CultuurNet\UDB3\Variations\ReadModel\Search\CriteriaFromParameterBagFactory;
use CultuurNet\UDB3\Variations\ReadModel\Search\RepositoryInterface;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ValueObjects\String\String;

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

        /* @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers
            ->get('/', 'variations_read_controller:search')
            ->bind('variations');

        $controllers->post('/', 'variations_write_controller:handle');

        $controllers->get('/{id}', 'variations_read_controller:get');
        $controllers->patch('/{id}', 'variations_edit_controller:edit');
        $controllers->delete('/{id}', 'variations_edit_controller:delete');

        return $controllers;
    }
}
