<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Error;

use Broadway\Repository\AggregateNotFoundException;
use CultuurNet\CalendarSummaryV3\FormatterException;
use CultuurNet\UDB3\ApiGuard\Request\RequestAuthenticationException;
use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\Deserializer\NotWellFormedException;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Event\Productions\EventCannotBeAddedToProduction;
use CultuurNet\UDB3\Event\Productions\EventCannotBeRemovedFromProduction;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Media\MediaObjectNotFoundException;
use CultuurNet\UDB3\Offer\CalendarTypeNotSupported;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\Security\CommandAuthorizationException;
use CultuurNet\UDB3\UiTPAS\Event\CommandHandling\Validation\EventHasTicketSalesException;
use Psr\Log\LoggerInterface;
use Respect\Validation\Exceptions\GroupedValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

final class ErrorLogger
{
    private const BAD_REQUESTS = [
        EntityNotFoundException::class,
        CommandAuthorizationException::class,
        NotFoundHttpException::class,
        MethodNotAllowedException::class,
        DataValidationException::class,
        GroupedValidationException::class,
        RequestAuthenticationException::class,
        MissingValueException::class,
        AggregateNotFoundException::class,
        MethodNotAllowedHttpException::class,
        EventHasTicketSalesException::class,
        MediaObjectNotFoundException::class,
        DocumentDoesNotExist::class,
        NotWellFormedException::class,
        BadRequestHttpException::class,
        FormatterException::class,
        EventCannotBeAddedToProduction::class,
        EventCannotBeRemovedFromProduction::class,
        AccessDeniedHttpException::class,
        AccessDeniedException::class,
        CalendarTypeNotSupported::class,
        ApiProblem::class,
    ];

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function log(Throwable $throwable): void
    {
        if (self::isBadRequestException($throwable)) {
            return;
        }

        // Include the original throwable as "exception" so that the Sentry monolog handler can process it correctly.
        $this->logger->error($throwable->getMessage(), ['exception' => $throwable]);
    }

    public static function isBadRequestException(Throwable $e): bool
    {
        // Use an instanceof check instead of in_array to also allow filtering on parent class or interface.
        foreach (self::BAD_REQUESTS as $badRequestExceptionClass) {
            if ($e instanceof $badRequestExceptionClass) {
                return true;
            }
        }
        return false;
    }
}
