<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\JSONLD;

use CultuurNet\UDB3\Http\JSONLD\ContextController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class ContextControllerProvider implements ControllerProviderInterface
{
    public const JSONLD_CONTEXT_CONTROLLER = 'json_ld_controller';

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

        return $controllers;
    }
}
