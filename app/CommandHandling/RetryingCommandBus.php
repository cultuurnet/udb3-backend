<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CommandHandling;

use CultuurNet\UDB3\EventSourcing\DBAL\DBALEventStoreException;

class RetryingCommandBus extends CommandBusDecoratorBase
{
    public const MAX_RETRIES = 15;

    public function dispatch($command): void
    {
        $attempt = 1;

        do {
            try {
                $this->decoratee->dispatch($command);
                return;
            } catch (DBALEventStoreException $e) {
                $lastException = $e;
            }
            $attempt++;
        } while ($attempt <= self::MAX_RETRIES);

        throw new RetriedCommandFailed(
            sprintf(
                'Command %s failed due to a DBAL event store exception after %s retries',
                get_class($command),
                $attempt
            ),
            0,
            $lastException
        );
    }
}
