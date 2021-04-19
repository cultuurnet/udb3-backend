<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\CommandHandling;

use CultuurNet\UDB3\CommandHandling\CommandBusDecoratorBase;
use CultuurNet\UDB3\EventSourcing\DBAL\DBALEventStoreException;

class RetryingCommandBus extends CommandBusDecoratorBase
{
    public const MAX_RETRIES = 3;

    public function dispatch($command)
    {
        $attempt = 1;
        do {
            try {
                return $this->decoratee->dispatch($command);
            } catch (DBALEventStoreException $e) {
                $lastException = $e;
            }
            $attempt++;
        } while ($attempt <= self::MAX_RETRIES);

        throw $lastException;
    }
}
