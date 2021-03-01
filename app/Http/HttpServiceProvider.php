<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Http;

use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle6\Client as Guzzle6ClientAdapter;
use Silex\Application;
use Silex\ServiceProviderInterface;

class HttpServiceProvider implements ServiceProviderInterface
{
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


    public function boot(Application $app)
    {
    }
}
