<?php

namespace CultuurNet\UDB3\Silex\CultureFeed;

use CultuurNet\Auth\ConsumerCredentials;
use CultuurNet\Auth\TokenCredentials;
use CultuurNet\UDB3\Silex\Impersonator;
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

        $app['culturefeed_token_credentials'] = $app->share(
            function (Application $app) {
                // Check first if we're impersonating someone.
                /* @var Impersonator $impersonator */
                $impersonator = $app['impersonator'];
                if ($impersonator->getTokenCredentials()) {
                    return $impersonator->getTokenCredentials();
                }

                // @todo Fetch from UiTID using JWT
                // @see https://jira.uitdatabank.be/browse/III-923
                // return new CultuurNet\Auth\TokenCredentials\TokenCredentials();

                return null;
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

                /* @var TokenCredentials $tokenCredentials */
                $tokenCredentials = $app['culturefeed_token_credentials'];

                $userCredentialsToken = null;
                $userCredentialsSecret = null;
                if ($tokenCredentials) {
                    $userCredentialsToken = $tokenCredentials->getToken();
                    $userCredentialsSecret = $tokenCredentials->getSecret();
                }

                $oauthClient = new \CultureFeed_DefaultOAuthClient(
                    $consumerCredentials->getKey(),
                    $consumerCredentials->getSecret(),
                    $userCredentialsToken,
                    $userCredentialsSecret
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
