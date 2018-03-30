<?php

namespace CultuurNet\UDB3\Silex\Place;

use CultuurNet\UDB3\Model\Import\Place\PlaceDocumentImporter;
use CultuurNet\UDB3\Model\Import\Place\PlaceLegacyBridgeCategoryResolver;
use CultuurNet\UDB3\Model\Import\PreProcessing\TermPreProcessingDocumentImporter;
use CultuurNet\UDB3\Model\Import\Validation\Taxonomy\Category\CategoriesExistValidator;
use CultuurNet\UDB3\Model\Import\Validation\Taxonomy\Category\EventTypeCountValidator;
use CultuurNet\UDB3\Model\Import\Validation\Taxonomy\Category\ThemeCountValidator;
use CultuurNet\UDB3\Model\Serializer\Place\PlaceDenormalizer;
use CultuurNet\UDB3\Model\Validation\Place\PlaceValidator;
use Respect\Validation\Rules\AllOf;
use Respect\Validation\Rules\Key;
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
            function (Application $app) {
                // @todo Move to dedicated "PlaceImportValidator" class.
                $extraRules = [
                    new Key(
                        'terms',
                        new AllOf(
                            new CategoriesExistValidator(new PlaceLegacyBridgeCategoryResolver(), 'place'),
                            new EventTypeCountValidator(),
                            new ThemeCountValidator()
                        ),
                        false
                    ),
                ];
                $validator = new PlaceValidator($extraRules);

                return new PlaceDenormalizer($validator);
            }
        );

        $app['place_importer'] = $app->share(
            function (Application $app) {
                $placeImporter = new PlaceDocumentImporter(
                    $app['place_repository'],
                    $app['place_denormalizer'],
                    $app['import_image_collection_factory'],
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
