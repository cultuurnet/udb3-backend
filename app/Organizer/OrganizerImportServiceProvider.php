<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Organizer;

use CultuurNet\UDB3\Model\Import\Organizer\OrganizerDocumentImporter;
use CultuurNet\UDB3\Model\Import\PreProcessing\LabelPreProcessingDocumentImporter;
use CultuurNet\UDB3\Model\Import\Validation\Organizer\OrganizerImportValidator;
use CultuurNet\UDB3\Model\Organizer\OrganizerIDParser;
use CultuurNet\UDB3\Model\Serializer\Organizer\OrganizerDenormalizer;
use CultuurNet\UDB3\Organizer\DBALWebsiteLookupService;
use CultuurNet\UDB3\Security\CultureFeedUserIdentification;
use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;

class OrganizerImportServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app['organizer_denormalizer'] = $app->share(
            function (Application $app) {
                $organizerValidatorFactory = new OrganizerImportValidator(
                    new DBALWebsiteLookupService(
                        $app['dbal_connection'],
                        'organizer_unique_websites'
                    ),
                    new OrganizerIDParser(),
                    new CultureFeedUserIdentification(
                        $app['current_user'],
                        $app['config']['user_permissions']
                    ),
                    $app[LabelServiceProvider::JSON_READ_REPOSITORY],
                    $app[LabelServiceProvider::RELATIONS_READ_REPOSITORY],
                    true
                );

                return new OrganizerDenormalizer($organizerValidatorFactory);
            }
        );

        $app['organizer_importer'] = $app->share(
            function (Application $app) {
                $organizerImporter = new OrganizerDocumentImporter(
                    $app['organizer_repository'],
                    $app['organizer_denormalizer'],
                    $app['imports_command_bus'],
                    $app['labels.labels_locked_for_import_repository']
                );

                $labelPreProcessor = new LabelPreProcessingDocumentImporter(
                    $organizerImporter,
                    $app[LabelServiceProvider::JSON_READ_REPOSITORY],
                    $app[LabelServiceProvider::RELATIONS_READ_REPOSITORY]
                );

                return $labelPreProcessor;
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
