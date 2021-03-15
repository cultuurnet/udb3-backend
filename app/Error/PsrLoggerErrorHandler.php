<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Error;

use Psr\Log\LoggerInterface;
use Throwable;

final class PsrLoggerErrorHandler implements ErrorHandler
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handle(Throwable $throwable): void
    {
        // Also include type, code, etc in the context because the "exception" key, which is needed for the Sentry
        // handler, does not get completely serialized in the log. So we lose some info if we don't include it
        // individually.
        $this->logger->error(
            $throwable->getMessage(),
            [
                'type' => get_class($throwable),
                'code' => $throwable->getCode(),
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
                'trace' => $throwable->getTraceAsString(),
                'exception' => $throwable,
            ]
        );
    }
}
