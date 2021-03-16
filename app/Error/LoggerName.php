<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Error;

final class LoggerName
{
    /**
     * @var string
     */
    private $fileNameWithoutSuffix;

    /**
     * @var string
     */
    private $loggerName;

    public function __construct(string $fileNameWithoutSuffix, ?string $customLoggerName = null)
    {
        $this->fileNameWithoutSuffix = $fileNameWithoutSuffix;
        $this->loggerName = $customLoggerName ?? 'logger.' . $this->fileNameWithoutSuffix;
    }

    public function getFileNameWithoutSuffix(): string
    {
        return $this->fileNameWithoutSuffix;
    }

    public function getLoggerName(): string
    {
        return $this->loggerName;
    }
}
