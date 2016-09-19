<?php

namespace CultuurNet\UDB3\Silex\JSONLD;

use CultuurNet\UDB3\Symfony\JSONLD\ContextController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use ValueObjects\String\String as StringLiteral;
use ValueObjects\Web\Url;

class ContextControllerProvider implements ControllerProviderInterface
{
    const JSONLD_CONTEXT_CONTROLLER = 'json_ld_controller';

    public function connect(Application $app)
    {
        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $app[self::JSONLD_CONTEXT_CONTROLLER] = $app->share(
            function (Application $app) {
                return new ContextController(
                    new StringLiteral('/var/www/udb-silex/web/api/context/'),
                    Url::fromNative('http://udb-silex.dev/')
                );
            }
        );

        $controllers->get('/{entityName}', self::JSONLD_CONTEXT_CONTROLLER . ':get');

        return $controllers;
    }
}
