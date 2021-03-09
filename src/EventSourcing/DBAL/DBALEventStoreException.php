<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventSourcing\DBAL;

use Broadway\EventStore\EventStoreException;
use Doctrine\DBAL\DBALException;

/**
 * Wraps exceptions thrown by the DBAL event store.
 */
class DBALEventStoreException extends EventStoreException
{
    public static function create(DBALException $exception): DBALEventStoreException
    {
        return new self('', 0, $exception);
    }
}
