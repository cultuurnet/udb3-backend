<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Event;

use CultuurNet\UDB3\Event\ReadModel\Relations\Doctrine\DBALEventRelationsRepository;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\ReadModel\MainLanguage\JSONLDMainLanguageQuery;
use Silex\Application;
use Silex\ServiceProviderInterface;

final class EventReadServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app[EventRelationsRepository::class] = $app::share(
            function ($app) {
                return new DBALEventRelationsRepository(
                    $app['dbal_connection']
                );
            }
        );

        $app['event_main_language_query'] = $app->share(
            function (Application $app) {
                $fallbackLanguage = new Language('nl');

                return new JSONLDMainLanguageQuery(
                    $app['event_jsonld_repository'],
                    $fallbackLanguage
                );
            }
        );
    }


    public function boot(Application $app)
    {
    }
}
