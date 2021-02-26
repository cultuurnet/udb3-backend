<?php

namespace CultuurNet\UDB3\Silex\CultureFeed;

use Silex\Application;
use Silex\ServiceProviderInterface;

class CultureFeedServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['culturefeed'] = $app->share(
            function (Application $app) {
                return new \CultureFeed($app['culturefeed_oauth_client']);
            }
        );

        $app['culturefeed_oauth_client'] = $app->share(
            function (Application $app) {
                $oauthClient = new \CultureFeed_DefaultOAuthClient(
                    $app['culturefeed.consumer.key'],
                    $app['culturefeed.consumer.secret']
                );
                $oauthClient->setEndpoint($app['culturefeed.endpoint']);

                return $oauthClient;
            }
        );
    }


    public function boot(Application $app)
    {
    }
}
