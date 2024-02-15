<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\ApiProblem;

use Broadway\Repository\AggregateNotFoundException;
use CultureFeed_Exception;
use CultureFeed_HttpException;
use CultuurNet\CalendarSummaryV3\FormatterException;
use CultuurNet\UDB3\ApiGuard\Request\RequestAuthenticationException;
use CultuurNet\UDB3\Calendar\EndDateCanNotBeEarlierThanStartDate;
use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\Deserializer\NotWellFormedException;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Event\Productions\EventCannotBeAddedToProduction;
use CultuurNet\UDB3\Event\Productions\EventCannotBeRemovedFromProduction;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Media\MediaObjectNotFoundException;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\Exception\StringIsInvalid;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\Security\CommandAuthorizationException;
use CultuurNet\UDB3\UiTPAS\Validation\ChangeNotAllowedByTicketSales;
use Error;
use Exception;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class ApiProblemFactory
{
    public static function createFromThrowable(
        Throwable $e,
        ?ServerRequestInterface $request = null
    ): ApiProblem {
        $routeParameters = $request ? new RouteParameters($request) : null;

        switch (true) {
            case $e instanceof ApiProblem:
                return $e;

            case $e instanceof Error:
                return ApiProblem::internalServerError();

            case $e instanceof ConvertsToApiProblem:
                return $e->toApiProblem();

            case $e instanceof RequestAuthenticationException:
                return ApiProblem::unauthorized($e->getMessage());

            case $e instanceof CommandAuthorizationException:
                return ApiProblem::forbidden(
                    sprintf(
                        'User %s has no permission "%s" on resource %s',
                        $e->getUserId(),
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
            case $e instanceof EntityNotFoundException:
            case $e instanceof MediaObjectNotFoundException:
                if (!$routeParameters) {
                    return ApiProblem::urlNotFound();
                }
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
                if ($routeParameters->hasMediaId()) {
                    return ApiProblem::mediaObjectNotFound($routeParameters->getMediaId());
                }
                return ApiProblem::urlNotFound();

            case $e instanceof DataValidationException:
                $problem = ApiProblem::blank('Invalid payload.', $e->getCode() ?: 400);
                $problem->setValidationMessages($e->getValidationMessages());
                return $problem;

            case $e instanceof CultureFeed_Exception:
            case $e instanceof CultureFeed_HttpException:
                return self::convertCultureFeedExceptionToApiProblem($e, $routeParameters);

            case $e instanceof MissingValueException:
            case $e instanceof ChangeNotAllowedByTicketSales:
            case $e instanceof NotWellFormedException:
            case $e instanceof FormatterException:
            case $e instanceof EventCannotBeAddedToProduction:
            case $e instanceof EventCannotBeRemovedFromProduction:
            case $e instanceof EndDateCanNotBeEarlierThanStartDate:
                return ApiProblem::blank($e->getMessage(), $e->getCode() ?: 400);

            case $e instanceof StringIsInvalid:
                return ApiProblem::bodyInvalidData(new SchemaError('/', $e->getMessage()));

            // Because almost any exception will be an instance of \Exception, we need to do a strict comparison of the
            // class name here to convert generic \Exception exceptions specifically.
            case get_class($e) === Exception::class:
                return self::convertGenericExceptionToApiProblem($e, $request);

            default:
                return self::convertToDefaultApiProblem($e);
        }
    }

    /**
     * Returns a default ApiProblem for Throwables that cannot be resolved to a specific type.
     */
    private static function convertToDefaultApiProblem(Throwable $e): ApiProblem
    {
        // Note: When we have improved the validation of most endpoints, we should make this return a plain
        // "Internal Server Error" instead.
        return ApiProblem::blank($e->getMessage(), $e->getCode() ?: 500);
    }

    /**
     * Usually we will convert \Exception to the default ApiProblem. However in some cases we want to convert generic
     * exceptions thrown by external libraries to a more specific ApiProblem based on e.g. their message.
     */
    private static function convertGenericExceptionToApiProblem(
        Throwable $e,
        ?ServerRequestInterface $request = null
    ): ApiProblem {
        $message = $e->getMessage();

        // While this looks like a generic timeout that could occur anywhere, the message is specifically crafted by the
        // culturefeed-php library. We use this library to contact either UiTPAS or UiTID v1 (the latter only to
        // validate the old API keys). We can make a safe guess by checking if the requested URL contains "uitpas" which
        // one is down. If there is no HTTP request (ie when running in CLI) this should not occur in theory, so it's
        // probably best to not convert it to a Bad Gateway problem but make it a generic error so it gets logged in
        // Sentry.
        if ($request && strpos($message, 'A curl error (28) occurred: Operation timed out after') !== false) {
            $service = strpos($request->getUri()->getPath(), 'uitpas') !== false ? 'UiTPAS' : 'UiTID v1';
            return ApiProblem::badGateway("Could not contact the {$service} servers. Please try again later.");
        }

        return self::convertToDefaultApiProblem($e);
    }

    private static function convertCultureFeedExceptionToApiProblem(
        Throwable $e,
        ?RouteParameters $routeParameters = null
    ): ApiProblem {
        $title = self::sanitizeCultureFeedErrorMessage($e->getMessage());

        if (strpos($title, 'event is not known in uitpas') !== false) {
            $message = 'Event not found in UiTPAS. Are you sure it is an UiTPAS event?';
            if ($routeParameters && $routeParameters->hasEventId()) {
                $message = sprintf(
                    'Event with id \'%s\' was not found in UiTPAS. Are you sure it is an UiTPAS event?',
                    $routeParameters->getEventId()
                );
            }
            return ApiProblem::urlNotFound($message);
        }

        if (strpos($title, 'Unknown organiser cdbid') !== false) {
            $message = 'Organizer not found in UiTPAS. Are you sure it is an UiTPAS organizer?';
            if ($routeParameters && $routeParameters->hasOrganizerId()) {
                $message = sprintf(
                    'Organizer with id \'%s\' was not found in UiTPAS. Are you sure it is an UiTPAS organizer?',
                    $routeParameters->getOrganizerId()
                );
            }
            return ApiProblem::urlNotFound($message);
        }

        if (strpos($title, 'event already has ticketsales') !== false) {
            $eventId = $routeParameters && $routeParameters->hasEventId() ? $routeParameters->getEventId() : null;
            return ApiProblem::eventHasUitpasTicketSales($eventId);
        }

        // In some cases the UiTPAS servers return a 404 error with an HTML page. In this case we treat it as UiTPAS
        // being down and return a Bad Gateway error, because the response is caused by the UiTPAS web server not
        // being configured / running correctly.
        // Note: The "reponse" below is not a typo, that's actually the wording in the message of the
        // CultureFeed_HttpException when a response is not 200...
        if ($e instanceof CultureFeed_HttpException &&
            strpos($title, 'The reponse for the HTTP request was not 200') !== false &&
            strpos($title, '<html') !== false) {
            return ApiProblem::badGateway('Could not contact the UiTPAS servers. Please try again later.');
        }

        return ApiProblem::blank($title, $e->getCode() ?: 500);
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
