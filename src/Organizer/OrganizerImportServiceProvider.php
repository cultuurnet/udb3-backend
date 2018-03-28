<?php

namespace CultuurNet\UDB3\Silex\Organizer;

use CultuurNet\UDB3\Model\Import\Organizer\OrganizerDocumentImporter;
use CultuurNet\UDB3\Model\Import\Validation\Organizer\OrganizerValidatorFactory;
use CultuurNet\UDB3\Model\Serializer\Organizer\OrganizerDenormalizer;
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
                $organizerValidatorFactory = new OrganizerValidatorFactory(
                    $app['dbal_connection'],
                    new CultureFeedUserIdentification(
                        $app['current_user'],
                        $app['config']['user_permissions']
                    ),
                    $app[LabelServiceProvider::JSON_READ_REPOSITORY],
                    $app[LabelServiceProvider::RELATIONS_READ_REPOSITORY]
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
