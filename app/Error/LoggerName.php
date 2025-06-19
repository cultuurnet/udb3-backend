<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Error;

final class LoggerName
{
    private string $fileNameWithoutSuffix;

    private string $loggerName;

    private function __construct(string $fileNameWithoutSuffix, ?string $customLoggerName = null)
    {
        $fileNameWithoutSuffix = str_replace('_', '-', $fileNameWithoutSuffix);
        $customLoggerName = $customLoggerName ? str_replace('_', '-', $customLoggerName) : null;

        $this->fileNameWithoutSuffix = $fileNameWithoutSuffix;
        $this->loggerName = $customLoggerName ?? $this->fileNameWithoutSuffix;
    }

    public function getFileNameWithoutSuffix(): string
    {
        return $this->fileNameWithoutSuffix;
    }

    public function getLoggerName(): string
    {
        return $this->loggerName;
    }

    public static function forCli(): self
    {
        return new self('cli');
    }

    public static function forWeb(): self
    {
        return new self('web');
    }

    public static function forConfig(): self
    {
        return new self('config');
    }

    public static function forAmqpWorker(string $workerName, ?string $suffix = null): self
    {
        $fileName = 'amqp.' . $workerName;
        $loggerName = self::appendSuffixToFilename($fileName, $suffix);
        return new self($fileName, $loggerName);
    }

    public static function forResqueWorker(string $queueName, ?string $suffix = null): self
    {
        $fileName = 'resque.' . $queueName;
        $loggerName = self::appendSuffixToFilename($fileName, $suffix);
        return new self($fileName, $loggerName);
    }

    public static function forService(string $serviceName, ?string $suffix = null): self
    {
        $fileName = 'service.' . $serviceName;
        $loggerName = self::appendSuffixToFilename($fileName, $suffix);
        return new self($fileName, $loggerName);
    }

    private static function appendSuffixToFilename(string $fileName, ?string $suffix = null): string
    {
        return $suffix ? $fileName . '.' . $suffix : $fileName;
    }
}
