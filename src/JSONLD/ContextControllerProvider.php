<?php

namespace CultuurNet\UDB3\Silex\JSONLD;

use CultuurNet\UDB3\Symfony\JSONLD\ContextController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;
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
                $contextDirectory = new StringLiteral($app['config']['jsonld']['context_directory']);

                $controller = new ContextController($contextDirectory);

                if (isset($app['config']['url']) && $app['config']['url'] !== ContextController::DEFAULT_BASE_PATH) {
                    $basePath = Url::fromNative($app['config']['url']);
                    return $controller->withCustomBasePath($basePath);
                } else {
                    return $controller;
                }
            }
        );

        $controllers->get('/{entityName}', self::JSONLD_CONTEXT_CONTROLLER . ':get');
        $controllers->get('/', self::JSONLD_CONTEXT_CONTROLLER . ':entryPoint');

        return $controllers;
    }
}
