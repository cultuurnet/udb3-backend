<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Error;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblemFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Exception\RuntimeException as SymfonyConsoleRuntimeException;
use Throwable;

final class ErrorLogger
{
    private const CLI_RUNTIME_EXCEPTIONS = [
        SymfonyConsoleRuntimeException::class,
    ];

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function log(Throwable $throwable): void
    {
        if (self::isBadRequestException($throwable) ||
            self::isBadGateway($throwable) ||
            self::isCliRuntimeException($throwable)) {
            return;
        }

        // Include the original throwable as "exception" so that the Sentry monolog handler can process it correctly.
        $this->logger->error($throwable->getMessage(), ['exception' => $throwable]);
    }

    public static function isCliRuntimeException(Throwable $e): bool
    {
        // Use foreach + instanceof instead of in_array() to also filter out child classes and/or interface
        // implementations.
        foreach (self::CLI_RUNTIME_EXCEPTIONS as $cliRuntimeExceptionClass) {
            if ($e instanceof $cliRuntimeExceptionClass) {
                return true;
            }
        }
        return false;
    }

    public static function isBadRequestException(Throwable $e): bool
    {
        $apiProblem = ApiProblemFactory::createFromThrowable($e);
        return $apiProblem->getStatus() >= 400 && $apiProblem->getStatus() < 500;
    }

    public static function isBadGateway(Throwable $e): bool
    {
        $apiProblem = ApiProblemFactory::createFromThrowable($e);
        return $apiProblem->getStatus() === 502;
    }
}
