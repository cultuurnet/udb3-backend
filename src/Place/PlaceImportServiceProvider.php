<?php

namespace CultuurNet\UDB3\Silex\Place;

use CultuurNet\UDB3\Model\Import\Place\PlaceDocumentImporter;
use CultuurNet\UDB3\Model\Import\Place\PlaceLegacyBridgeCategoryResolver;
use CultuurNet\UDB3\Model\Import\PreProcessing\TermPreProcessingDocumentImporter;
use CultuurNet\UDB3\Model\Import\Validation\Place\PlaceValidatorFactory;
use CultuurNet\UDB3\Model\Serializer\Place\PlaceDenormalizer;
use Silex\Application;
use Silex\ServiceProviderInterface;

class PlaceImportServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app['place_denormalizer'] = $app->share(
            function () {
                return new PlaceDenormalizer(new PlaceValidatorFactory());
            }
        );

        $app['place_importer'] = $app->share(
            function (Application $app) {
                $placeImporter = new PlaceDocumentImporter(
                    $app['place_repository'],
                    $app['place_denormalizer'],
                    $app['event_command_bus']
                );

                $termPreProcessor = new TermPreProcessingDocumentImporter(
                    $placeImporter,
                    new PlaceLegacyBridgeCategoryResolver()
                );

                return $termPreProcessor;
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function boot(Application $app)
    {
    }
}
