<?php

namespace CultuurNet\UDB3\Silex\Http;

use CultuurNet\UDB3\Http\ApiKeyPsr7RequestAuthorizer;
use CultuurNet\UDB3\Http\GuzzlePsr7Factory;
use CultuurNet\UDB3\Http\JwtPsr7RequestAuthorizer;
use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle6\Client as Guzzle6ClientAdapter;
use Silex\Application;
use Silex\ServiceProviderInterface;

class HttpServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['http.guzzle'] = $app->share(
            function () {
                return new Guzzle6ClientAdapter(
                    new GuzzleClient()
                );
            }
        );

        $app['http.guzzle_psr7_factory'] = $app->share(
            function () {
                return new GuzzlePsr7Factory();
            }
        );

        $app['http.jwt_request_authorizer'] = $app->share(
            function (Application $app) {
                return new JwtPsr7RequestAuthorizer(
                    $app['jwt']
                );
            }
        );

        $app['http.api_key_request_authorizer'] = $app->share(
            function (Application $app) {
                return new ApiKeyPsr7RequestAuthorizer(
                    $app['api_key']
                );
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
