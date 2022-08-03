<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Place;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\ReadModel\MainLanguage\JSONLDMainLanguageQuery;
use CultuurNet\UDB3\Place\ReadModel\Relations\Doctrine\DBALPlaceRelationsRepository;
use CultuurNet\UDB3\Place\ReadModel\Relations\PlaceRelationsRepository;
use CultuurNet\UDB3\Place\ReadModel\Relations\Projector;
use Silex\Application;
use Silex\ServiceProviderInterface;

final class PlaceReadServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app['place_relations_projector'] = $app->share(
            function ($app) {
                return new Projector(
                    $app[PlaceRelationsRepository::class]
                );
            }
        );

        $app[PlaceRelationsRepository::class] = $app::share(
            function ($app) {
                return new DBALPlaceRelationsRepository(
                    $app['dbal_connection']
                );
            }
        );

        $app['place_main_language_query'] = $app->share(
            function (Application $app) {
                $fallbackLanguage = new Language('nl');

                return new JSONLDMainLanguageQuery(
                    $app['place_jsonld_repository'],
                    $fallbackLanguage
                );
            }
        );
    }


    public function boot(Application $app)
    {
    }
}
