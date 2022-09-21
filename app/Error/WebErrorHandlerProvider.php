<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Error;

use Exception;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;

class WebErrorHandlerProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app[ErrorLogger::class] = $app::share(
            function (Application $app): ErrorLogger {
                return new ErrorLogger(
                    LoggerFactory::create($app, LoggerName::forWeb())
                );
            }
        );

        $app[WebErrorHandler::class] = $app::share(
            function (Application $app): WebErrorHandler {
                return new WebErrorHandler(
                    $app[ErrorLogger::class],
                    $app['debug'] === true
                );
            }
        );

        $app->error(
            function (Exception $e) use ($app) {
                $request = (new DiactorosFactory())->createRequest(
                    $app['request_stack']->getCurrentRequest()
                );

                /** @var WebErrorHandler $webErrorHandler */
                $webErrorHandler = $app[WebErrorHandler::class];
                $response = $webErrorHandler->handle($request, $e);
                return $response->toHttpFoundationResponse();
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}
