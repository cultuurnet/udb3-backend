<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Organizer;

use CultuurNet\UDB3\Model\Import\Validation\Organizer\OrganizerImportValidator;
use CultuurNet\UDB3\Model\Organizer\OrganizerIDParser;
use CultuurNet\UDB3\Model\Serializer\Organizer\OrganizerDenormalizer;
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
                    new OrganizerIDParser(),
                    $app['current_user_id'],
                    $app[LabelServiceProvider::JSON_READ_REPOSITORY],
                    $app[LabelServiceProvider::RELATIONS_READ_REPOSITORY]
                );

                return new OrganizerDenormalizer($organizerValidatorFactory);
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
