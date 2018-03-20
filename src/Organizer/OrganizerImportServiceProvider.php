<?php

namespace CultuurNet\UDB3\Silex\Organizer;

use CultuurNet\UDB3\Model\Import\Organizer\OrganizerDocumentImporter;
use CultuurNet\UDB3\Model\Import\Validation\Organizer\OrganizerHasUniqueUrlValidator;
use CultuurNet\UDB3\Model\Organizer\OrganizerIDParser;
use CultuurNet\UDB3\Model\Serializer\Organizer\OrganizerDenormalizer;
use CultuurNet\UDB3\Model\Validation\Organizer\OrganizerValidator;
use CultuurNet\UDB3\Organizer\DBALWebsiteLookupService;
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
                // @todo Move to dedicated "OrganizerImportValidator" class.
                $extraRules = [
                    new OrganizerHasUniqueUrlValidator(
                        new OrganizerIDParser(),
                        new DBALWebsiteLookupService(
                            $app['dbal_connection'],
                            'organizer_unique_websites'
                        )
                    ),
                ];
                $validator = new OrganizerValidator($extraRules, true);

                return new OrganizerDenormalizer($validator);
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
