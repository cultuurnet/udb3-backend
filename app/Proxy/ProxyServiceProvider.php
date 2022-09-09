<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Proxy;

use CultuurNet\UDB3\Http\Proxy\FilterPathRegex;
use CultuurNet\UDB3\Http\Proxy\Proxy;
use CultuurNet\UDB3\Model\ValueObject\Web\Hostname;
use CultuurNet\UDB3\Model\ValueObject\Web\PortNumber;
use GuzzleHttp\Client;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ProxyServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['cdbxml_proxy'] = $app->share(
            function ($app) {
                return Proxy::createWithCdbXmlFilter(
                    $app['config']['cdbxml_proxy']['accept'],
                    new Hostname($app['config']['cdbxml_proxy']['redirect_domain']),
                    new PortNumber($app['config']['cdbxml_proxy']['redirect_port']),
                    new Client()
                );
            }
        );

        $app['search_proxy'] = $app->share(
            function ($app) {
                return $app['get_request_proxy_factory'](
                    $app['config']['search_proxy']['pathRegex'],
                    $app['config']['search_proxy']['redirect_domain'],
                    $app['config']['search_proxy']['redirect_port']
                );
            }
        );

        $app['get_request_proxy_factory'] = $app->protect(
            function ($pathRegex, $redirectDomain, $redirectPort) {
                return Proxy::createWithSearchFilter(
                    new FilterPathRegex($pathRegex),
                    'GET',
                    new Hostname($redirectDomain),
                    new PortNumber($redirectPort),
                    new Client()
                );
            }
        );
    }


    public function boot(Application $app)
    {
    }
}
