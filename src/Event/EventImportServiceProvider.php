<?php

namespace CultuurNet\UDB3\Silex\Event;

use CultuurNet\UDB3\Model\Event\EventIDParser;
use CultuurNet\UDB3\Model\Import\Event\EventDocumentImporter;
use CultuurNet\UDB3\Model\Import\Event\EventLegacyBridgeCategoryResolver;
use CultuurNet\UDB3\Model\Import\PreProcessing\LocationPreProcessingDocumentImporter;
use CultuurNet\UDB3\Model\Import\PreProcessing\TermPreProcessingDocumentImporter;
use CultuurNet\UDB3\Model\Import\Validation\Event\EventDocumentValidator;
use CultuurNet\UDB3\Model\Place\PlaceIDParser;
use CultuurNet\UDB3\Model\Serializer\Event\EventDenormalizer;
use CultuurNet\UDB3\Security\CultureFeedUserIdentification;
use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;

class EventImportServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app['event_denormalizer'] = $app->share(
            function (Application $app) {
                return new EventDenormalizer(
                    new EventDocumentValidator(
                        $app['place_jsonld_repository'],
                        new EventIDParser(),
                        new CultureFeedUserIdentification(
                            $app['current_user'],
                            $app['config']['user_permissions']
                        ),
                        $app[LabelServiceProvider::JSON_READ_REPOSITORY],
                        $app[LabelServiceProvider::RELATIONS_READ_REPOSITORY]
                    )
                );
            }
        );

        $app['event_importer'] = $app->share(
            function (Application $app) {
                $eventImporter = new EventDocumentImporter(
                    $app['event_repository'],
                    $app['event_denormalizer'],
                    $app['event_command_bus']
                );

                $termPreProcessor = new TermPreProcessingDocumentImporter(
                    $eventImporter,
                    new EventLegacyBridgeCategoryResolver()
                );

                $locationPreProcessor = new LocationPreProcessingDocumentImporter(
                    $termPreProcessor,
                    new PlaceIDParser(),
                    $app['place_jsonld_repository']
                );

                return $locationPreProcessor;
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
