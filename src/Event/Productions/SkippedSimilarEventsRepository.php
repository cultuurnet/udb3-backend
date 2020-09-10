<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\Label\ReadModels\Doctrine\AbstractDBALRepository;
use Doctrine\DBAL\Connection;
use ValueObjects\StringLiteral\StringLiteral;

class SkippedSimilarEventsRepository extends AbstractDBALRepository
{
    public const TABLE_NAME = 'similar_events_skipped';

    public function __construct(Connection $connection)
    {
        parent::__construct($connection, new StringLiteral('similar_events_skipped'));
    }

    public function add(SimilarEventPair $eventPair): void
    {
        $this->getConnection()->insert(
            $this->getTableName()->toNative(),
            [
                'event1' => $eventPair->getEventOne(),
                'event2' => $eventPair->getEventTwo(),
            ]
        );
    }
}
