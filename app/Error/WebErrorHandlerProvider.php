<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Error;

use Broadway\Repository\AggregateNotFoundException;
use CultureFeed_Exception;
use CultureFeed_HttpException;
use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\ConvertsToApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\ApiProblemJsonResponse;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\Security\CommandAuthorizationException;
use Error;
use Exception;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use Psr\Http\Message\ServerRequestInterface;
use Respect\Validation\Exceptions\GroupedValidationException;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Throwable;

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

        $app->error(
            function (Exception $e) use ($app) {
                $app[ErrorLogger::class]->log($e);

                $request = (new DiactorosFactory())->createRequest(
                    $app['request_stack']->getCurrentRequest()
                );

                $defaultStatus = ErrorLogger::isBadRequestException($e) ? 400 : 500;

                $problem = WebErrorHandler::createNewApiProblem($request, $e, $defaultStatus, $app['debug'] === true);
                return (new ApiProblemJsonResponse($problem))->toHttpFoundationResponse();
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}
