<?php

namespace CultuurNet\UDB3\Silex\Http;

use CultuurNet\UDB3\Http\ApiKeyPsr7RequestAuthorizer;
use CultuurNet\UDB3\Http\PassthroughPsr7RequestAuthorizer;
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

        $app['http.api_key_request_authorizer'] = $app->share(
            function (Application $app) {
                if ($app['api_key']) {
                    return new ApiKeyPsr7RequestAuthorizer(
                        $app['api_key']
                    );
                } else {
                    return new PassthroughPsr7RequestAuthorizer();
                }
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
