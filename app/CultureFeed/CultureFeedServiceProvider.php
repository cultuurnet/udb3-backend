<?php

namespace CultuurNet\UDB3\Silex\CultureFeed;

use CultuurNet\Auth\ConsumerCredentials;
use CultuurNet\Auth\TokenCredentials;
use CultuurNet\UDB3\Jwt\Udb3Token;
use CultuurNet\UDB3\Silex\Impersonator;
use CultuurNet\UitidCredentials\UitidCredentialsFetcher;
use Silex\Application;
use Silex\ServiceProviderInterface;

class CultureFeedServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['culturefeed_consumer_credentials'] = $app->share(
            function (Application $app) {
                return new ConsumerCredentials(
                    $app['culturefeed.consumer.key'],
                    $app['culturefeed.consumer.secret']
                );
            }
        );

        $app['culturefeed_uitid_credentials_fetcher'] = $app->share(
            function () use ($app) {
                $baseUrl = $app['culturefeed.endpoint'];

                // Strip off /uitid/rest/ from the endpoint, the
                // UitidCredentialsFetcher appends this itself.
                $baseUrl = preg_replace('@/uitid/rest$@', '', $baseUrl);

                $consumerCredentials = $app['culturefeed_consumer_credentials'];
                return new UitidCredentialsFetcher($baseUrl, $consumerCredentials);
            }
        );

        $app['culturefeed'] = $app->share(
            function (Application $app) {
                return new \CultureFeed($app['culturefeed_oauth_client']);
            }
        );

        $app['culturefeed_oauth_client'] = $app->share(
            function (Application $app) {
                /* @var ConsumerCredentials $consumerCredentials */
                $consumerCredentials = $app['culturefeed_consumer_credentials'];

                $oauthClient = new \CultureFeed_DefaultOAuthClient(
                    $consumerCredentials->getKey(),
                    $consumerCredentials->getSecret()
                );
                $oauthClient->setEndpoint($app['culturefeed.endpoint']);

                return $oauthClient;
            }
        );
    }

    /**
     * @param Application $app
     */
    public function boot(Application $app)
    {
    }
}
