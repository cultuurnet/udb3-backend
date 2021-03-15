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
            $record['context']['type'] = get_class($exception);
            $record['context']['code'] = $exception->getCode();
            $record['context']['file'] = $exception->getFile();
            $record['context']['line'] = $exception->getLine();
            $record['context']['trace'] = $exception->getTraceAsString();
            unset($record['context']['exception']);
        }
        return $record;
    }
}
