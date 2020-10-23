<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex;

use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Decorates a PSR logger to also send all errors and emergencies that contain a Throwable in their context (in an
 * "exception" key) to Sentry.
 * Originally written to send error/emergency logs written by CultuurNet\BroadwayAMQP\AbstractConsumer
 * (i.e. all AMQP consumers) to Sentry.
 */
final class SentryPsrLoggerDecorator implements LoggerInterface
{
    /**
     * @var SentryErrorHandler
     */
    private $sentryHandler;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(SentryErrorHandler $sentryHandler, LoggerInterface $logger)
    {
        $this->sentryHandler = $sentryHandler;
        $this->logger = $logger;
    }

    public function error($message, array $context = array()): void
    {
        $this->sendThrowableToSentry($context);
        $this->logger->error($message, $context);
    }

    public function emergency($message, array $context = array()): void
    {
        $this->sendThrowableToSentry($context);
        $this->logger->emergency($message, $context);
    }

    public function alert($message, array $context = array()): void
    {
        $this->logger->alert($message, $context);
    }

    public function critical($message, array $context = array()): void
    {
        $this->logger->critical($message, $context);
    }

    public function warning($message, array $context = array()): void
    {
        $this->logger->warning($message, $context);
    }

    public function notice($message, array $context = array()): void
    {
        $this->logger->notice($message, $context);
    }

    public function info($message, array $context = array()): void
    {
        $this->logger->info($message, $context);
    }

    public function debug($message, array $context = array()): void
    {
        $this->logger->debug($message, $context);
    }

    public function log($level, $message, array $context = array()): void
    {
        $this->logger->log($level, $message, $context);
    }

    private function sendThrowableToSentry(array $context = array()): void
    {
        $throwable = $context['exception'] ?? null;
        if (!$throwable || !($throwable instanceof Throwable)) {
            return;
        }

        $this->sentryHandler->handle($throwable);
    }
}
