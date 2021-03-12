<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex;

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
        $this->logger->error(
            $throwable->getMessage(),
            [
                'exception' => $throwable,
                'type' => get_class($throwable),
                'code' => $throwable->getCode(),
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
                'trace' => $throwable->getTraceAsString(),
            ]
        );
    }
}
