<?php

namespace CultuurNet\UDB3\Silex\Organizer;

use CultuurNet\UDB3\Model\Import\Organizer\OrganizerDocumentImporter;
use CultuurNet\UDB3\Model\Import\Validation\Organizer\OrganizerDocumentValidator;
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
                $organizerValidatorFactory = new OrganizerDocumentValidator(
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
                return new OrganizerDocumentImporter(
                    $app['organizer_repository'],
                    $app['organizer_denormalizer'],
                    $app['event_command_bus']
                );
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
