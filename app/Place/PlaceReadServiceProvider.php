<?php

namespace CultuurNet\UDB3\Silex\Place;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\ReadModel\MainLanguage\JSONLDMainLanguageQuery;
use Silex\Application;
use Silex\ServiceProviderInterface;

class PlaceReadServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
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
