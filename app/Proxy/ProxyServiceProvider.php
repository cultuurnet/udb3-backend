<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Proxy;

use CultuurNet\UDB3\Http\Proxy\CdbXmlProxy;
use CultuurNet\UDB3\Http\Proxy\FilterPathMethodProxy;
use CultuurNet\UDB3\Http\Proxy\FilterPathRegex;
use GuzzleHttp\Client;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Hostname;
use ValueObjects\Web\PortNumber;

class ProxyServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['cdbxml_proxy'] = $app->share(
            function ($app) {
                return new CdbXmlProxy(
                    new StringLiteral($app['config']['cdbxml_proxy']['accept']),
                    new Hostname($app['config']['cdbxml_proxy']['redirect_domain']),
                    new PortNumber($app['config']['cdbxml_proxy']['redirect_port']),
                    new DiactorosFactory(),
                    new HttpFoundationFactory(),
                    new Client()
                );
            }
        );

        $app['calendar_summary_proxy'] = $app->share(
            function ($app) {
                return $app['get_request_proxy_factory'](
                    $app['config']['calendar_summary_proxy']['pathRegex'],
                    $app['config']['calendar_summary_proxy']['redirect_domain'],
                    $app['config']['calendar_summary_proxy']['redirect_port']
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
                return new FilterPathMethodProxy(
                    new FilterPathRegex($pathRegex),
                    new StringLiteral('GET'),
                    new Hostname($redirectDomain),
                    new PortNumber($redirectPort),
                    new DiactorosFactory(),
                    new HttpFoundationFactory(),
                    new Client()
                );
            }
        );
    }


    public function boot(Application $app)
    {
    }
}
