<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Error;

use Crell\ApiProblem\ApiProblem;
use CultureFeed_Exception;
use CultureFeed_HttpException;
use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblemException;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblems;
use CultuurNet\UDB3\HttpFoundation\Response\ApiProblemJsonResponse;
use CultuurNet\UDB3\Security\CommandAuthorizationException;
use Error;
use Exception;
use Respect\Validation\Exceptions\GroupedValidationException;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

class WebErrorHandlerProvider implements ServiceProviderInterface
{
    private static $debug = false;

    public function register(Application $app): void
    {
        self::$debug = $app['debug'] === true;

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

                $defaultStatus = ApiProblemJsonResponse::HTTP_INTERNAL_SERVER_ERROR;
                if (ErrorLogger::isBadRequestException($e)) {
                    $defaultStatus = ApiProblemJsonResponse::HTTP_BAD_REQUEST;
                }

                $problem = $this::createNewApiProblem($e, $defaultStatus);

                return new ApiProblemJsonResponse($problem);
            }
        );
    }

    public static function createNewApiProblem(Throwable $e, int $defaultStatus): ApiProblem
    {
        $problem = new ApiProblem($e->getMessage());
        $problem->setStatus($e->getCode() ?: $defaultStatus);

        if ($e instanceof Error) {
            $problem = ApiProblems::internalServerError();
        }

        if ($e instanceof ApiProblemException) {
            $problem = $e->getApiProblem();
        }

        if ($e instanceof AccessDeniedException ||
            $e instanceof AccessDeniedHttpException
        ) {
            $problem = ApiProblems::forbidden();
        }

        if ($e instanceof CommandAuthorizationException) {
            $problem = ApiProblems::forbidden(
                sprintf(
                    'User %s has no permission "%s" on resource %s',
                    $e->getUserId()->toNative(),
                    $e->getCommand()->getPermission()->toNative(),
                    $e->getCommand()->getItemId()
                )
            );
        }

        if ($e instanceof DataValidationException) {
            $problem->setTitle('Invalid payload.');
            $problem['validation_messages'] = $e->getValidationMessages();
        }

        if ($e instanceof GroupedValidationException) {
            $problem['validation_messages'] = $e->getMessages();
        }

        if ($e instanceof CultureFeed_Exception || $e instanceof CultureFeed_HttpException) {
            $title = $problem->getTitle();

            // Remove "URL CALLED" and everything after it.
            // E.g. "event is not known in uitpas URL CALLED: https://acc.uitid.be/uitid/rest/uitpas/cultureevent/..."
            // becomes "event is not known in uitpas ".
            // The trailing space could easily be removed but it's there for backward compatibility with systems that
            // might have implemented a comparison on the error message when this was introduced in udb3-uitpas-service
            // in the past.
            $formattedTitle = preg_replace('/URL CALLED.*/', '', $title);
            $problem->setTitle($formattedTitle);
        }

        if (self::$debug) {
            $problem['debug'] = ContextExceptionConverterProcessor::convertThrowableToArray($e);
        }

        return $problem;
    }

    public function boot(Application $app): void
    {
        self::$debug = $app['debug'] === true;
    }
}
