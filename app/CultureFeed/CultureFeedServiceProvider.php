<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\CultureFeed;

use Silex\Application;
use Silex\ServiceProviderInterface;

class CultureFeedServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['culturefeed'] = $app->share(
            function (Application $app) {
                $oauthClient = new \CultureFeed_DefaultOAuthClient(
                    $app['culturefeed.consumer.key'],
                    $app['culturefeed.consumer.secret']
                );
                $oauthClient->setEndpoint($app['culturefeed.endpoint']);

                return new \CultureFeed($oauthClient);
            }
        );
    }


    public function boot(Application $app)
    {
    }
}
