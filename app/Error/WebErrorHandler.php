<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Error;

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
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class WebErrorHandler implements MiddlewareInterface
{
    private ErrorLogger $errorLogger;
    private bool $debugMode;

    public function __construct(ErrorLogger $errorLogger, bool $debugMode)
    {
        $this->errorLogger = $errorLogger;
        $this->debugMode = $debugMode;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $e) {
            return $this->handle($request, $e);
        }
    }

    public function handle(ServerRequestInterface $request, Throwable $e): ApiProblemJsonResponse
    {
        $this->errorLogger->log($e);
        $defaultStatus = ErrorLogger::isBadRequestException($e) ? 400 : 500;
        $problem = self::createNewApiProblem($request, $e, $defaultStatus, $this->debugMode);
        return new ApiProblemJsonResponse($problem);
    }

    public static function createNewApiProblem(ServerRequestInterface $request, Throwable $e, int $defaultStatus, bool $debug = false): ApiProblem
    {
        $problem = self::convertThrowableToApiProblem($request, $e, $defaultStatus);
        if ($debug) {
            $problem->setDebugInfo(ContextExceptionConverterProcessor::convertThrowableToArray($e));
        }
        return $problem;
    }

    private static function convertThrowableToApiProblem(ServerRequestInterface $request, Throwable $e, int $defaultStatus): ApiProblem
    {
        switch (true) {
            case $e instanceof ApiProblem:
                return $e;

            case $e instanceof Error:
                return ApiProblem::internalServerError();

            case $e instanceof ConvertsToApiProblem:
                return $e->toApiProblem();

            case $e instanceof CommandAuthorizationException:
                return ApiProblem::forbidden(
                    sprintf(
                        'User %s has no permission "%s" on resource %s',
                        $e->getUserId()->toNative(),
                        $e->getCommand()->getPermission()->toString(),
                        $e->getCommand()->getItemId()
                    )
                );

            case $e instanceof NotFoundException:
                return ApiProblem::urlNotFound();

            case $e instanceof MethodNotAllowedException:
                $details = null;
                $headers = $e->getHeaders();
                $allowed = $headers['Allow'] ?? null;
                if ($allowed !== null) {
                    $details = 'Allowed: ' . $allowed;
                }
                return ApiProblem::methodNotAllowed($details);

            // Do a best effort to convert "not found" exceptions into an ApiProblem with preferably a detail mentioning
            // what kind of resource and with what id could not be found. Since the exceptions themselves do not contain
            // enough info to detect this, we need to get this info from the current request. However this is not
            // perfect because for example an event route might try to load another related resource and if that one is
            // not found this logic might say that the event is not found. When that happens, try to manually catch the
            // exception in the request handler or command handler and convert it to an ApiProblem with a better detail.
            case $e instanceof AggregateNotFoundException:
            case $e instanceof DocumentDoesNotExist:
                $routeParameters = new RouteParameters($request);
                if ($routeParameters->hasEventId()) {
                    return ApiProblem::eventNotFound($routeParameters->getEventId());
                }
                if ($routeParameters->hasPlaceId()) {
                    return ApiProblem::placeNotFound($routeParameters->getPlaceId());
                }
                if ($routeParameters->hasOrganizerId()) {
                    return ApiProblem::organizerNotFound($routeParameters->getOrganizerId());
                }
                if ($routeParameters->hasOfferId() && $routeParameters->hasOfferType()) {
                    return ApiProblem::offerNotFound($routeParameters->getOfferType(), $routeParameters->getOfferId());
                }
                if ($routeParameters->hasRoleId()) {
                    return ApiProblem::roleNotFound($routeParameters->getRoleId()->toString());
                }
                return ApiProblem::urlNotFound();

            case $e instanceof DataValidationException:
                $problem = ApiProblem::blank('Invalid payload.', $e->getCode() ?: $defaultStatus);
                $problem->setValidationMessages($e->getValidationMessages());
                return $problem;

            case $e instanceof CultureFeed_Exception:
            case $e instanceof CultureFeed_HttpException:
                return self::convertCultureFeedExceptionToApiProblem($request, $e, $defaultStatus);

            default:
                return ApiProblem::blank($e->getMessage(), $e->getCode() ?: $defaultStatus);
        }
    }

    private static function convertCultureFeedExceptionToApiProblem(
        ServerRequestInterface $request,
        Throwable $e,
        int $defaultStatus
    ): ApiProblem {
        $title = self::sanitizeCultureFeedErrorMessage($e->getMessage());
        return ApiProblem::blank($title, $e->getCode() ?: $defaultStatus);
    }

    /**
     * Remove "URL CALLED" and everything after it.
     * E.g. "event is not known in uitpas URL CALLED: https://acc.uitid.be/uitid/rest/uitpas/cultureevent/..."
     * becomes "event is not known in uitpas ".
     * The trailing space could easily be removed but it's there for backward compatibility with systems that might have
     * implemented a comparison on the error message when this was introduced in udb3-uitpas-service in the past.
     */
    private static function sanitizeCultureFeedErrorMessage(string $errorMessage): string
    {
        return preg_replace('/URL CALLED.*/', '', $errorMessage);
    }
}
