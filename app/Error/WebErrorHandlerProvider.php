<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Error;

use Broadway\Repository\AggregateNotFoundException;
use CultureFeed_Exception;
use CultureFeed_HttpException;
use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\ApiProblemJsonResponse;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\Security\CommandAuthorizationException;
use Error;
use Exception;
use Respect\Validation\Exceptions\GroupedValidationException;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\Request;
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
                $request = $app['request_stack']->getCurrentRequest();

                $defaultStatus = ErrorLogger::isBadRequestException($e) ? 400 : 500;

                $problem = $this::createNewApiProblem($request, $e, $defaultStatus);
                return (new ApiProblemJsonResponse($problem))->toHttpFoundationResponse();
            }
        );
    }

    public static function createNewApiProblem(Request $request, Throwable $e, int $defaultStatus): ApiProblem
    {
        $problem = self::convertThrowableToApiProblem($request, $e, $defaultStatus);
        if (self::$debug) {
            $problem->setDebugInfo(ContextExceptionConverterProcessor::convertThrowableToArray($e));
        }
        return $problem;
    }

    private static function convertThrowableToApiProblem(Request $request, Throwable $e, int $defaultStatus): ApiProblem
    {
        switch (true) {
            case $e instanceof ApiProblem:
                return $e;

            case $e instanceof Error:
                return ApiProblem::internalServerError();

            case $e instanceof AccessDeniedException:
            case $e instanceof AccessDeniedHttpException:
                return ApiProblem::forbidden();

            case $e instanceof CommandAuthorizationException:
                return ApiProblem::forbidden(
                    sprintf(
                        'User %s has no permission "%s" on resource %s',
                        $e->getUserId()->toNative(),
                        $e->getCommand()->getPermission()->toNative(),
                        $e->getCommand()->getItemId()
                    )
                );

            // Do a best effort to convert "not found" exceptions into an ApiProblem with preferably a detail mentioning
            // what kind of resource and with what id could not be found. Since the exceptions themselves do not contain
            // enough info to detect this, we need to get this info from the current request. However this is not
            // perfect because for example an event route might try to load another related resource and if that one is
            // not found this logic might say that the event is not found. When that happens, try to manually catch the
            // exception in the request handler or command handler and convert it to an ApiProblem with a better detail.
            case $e instanceof AggregateNotFoundException:
            case $e instanceof DocumentDoesNotExist:
                $psr7Request = (new DiactorosFactory())->createRequest($request);
                $routeParameters = new RouteParameters($psr7Request);
                if ($routeParameters->hasEventId()) {
                    return ApiProblem::eventNotFound($routeParameters->getEventId());
                }
                if ($routeParameters->hasPlaceId()) {
                    return ApiProblem::placeNotFound($routeParameters->getPlaceId());
                }
                if ($routeParameters->hasOfferId() && $routeParameters->hasOfferType()) {
                    return ApiProblem::offerNotFound($routeParameters->getOfferType(), $routeParameters->getOfferId());
                }
                return ApiProblem::urlNotFound();

            case $e instanceof DataValidationException:
                $problem = ApiProblem::blank('Invalid payload.', $e->getCode() ?: $defaultStatus);
                $problem->setValidationMessages($e->getValidationMessages());
                return $problem;

            case $e instanceof GroupedValidationException:
                $problem = ApiProblem::blank($e->getMessage(), $e->getCode() ?: $defaultStatus);
                $problem->setValidationMessages($e->getMessages());
                return $problem;

            // Remove "URL CALLED" and everything after it.
            // E.g. "event is not known in uitpas URL CALLED: https://acc.uitid.be/uitid/rest/uitpas/cultureevent/..."
            // becomes "event is not known in uitpas ".
            // The trailing space could easily be removed but it's there for backward compatibility with systems that
            // might have implemented a comparison on the error message when this was introduced in udb3-uitpas-service
            // in the past.
            case $e instanceof CultureFeed_Exception:
            case $e instanceof CultureFeed_HttpException:
                $title = $e->getMessage();
                $formattedTitle = preg_replace('/URL CALLED.*/', '', $title);
                return ApiProblem::blank($formattedTitle, $e->getCode() ?: $defaultStatus);

            default:
                return ApiProblem::blank($e->getMessage(), $e->getCode() ?: $defaultStatus);
        }
    }

    public function boot(Application $app): void
    {
        self::$debug = $app['debug'] === true;
    }
}
