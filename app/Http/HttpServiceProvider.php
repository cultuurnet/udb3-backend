<?php

namespace CultuurNet\UDB3\Silex\Http;

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
    }

    /**
     * @param Application $app
     */
    public function boot(Application $app)
    {
    }
}
