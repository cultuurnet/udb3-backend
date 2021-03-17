<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Error;

use Monolog\Processor\ProcessorInterface;
use Throwable;

final class ContextExceptionConverterProcessor implements ProcessorInterface
{
    public function __invoke(array $record): array
    {
        // Throwable classes don't get serialized correctly when writing to a file for example, so extract the useful
        // information and add it to the context.
        $exception = $record['context']['exception'] ?? null;
        if ($exception instanceof Throwable) {
            $record['context'] = array_merge($record['context'], self::convertThrowableToArray($exception));
            unset($record['context']['exception']);
        }
        return $record;
    }

    public static function convertThrowableToArray(Throwable $e): array
    {
        return [
            'type' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ];
    }
}
