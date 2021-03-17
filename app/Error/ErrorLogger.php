<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Error;

use Psr\Log\LoggerInterface;
use Throwable;

final class ErrorLogger
{
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
        // Include the original throwable as "exception" so that the Sentry monolog handler can process it correctly.
        $this->logger->error($throwable->getMessage(), ['exception' => $throwable]);
    }
}
