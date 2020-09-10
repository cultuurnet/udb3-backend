<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\Label\ReadModels\Doctrine\AbstractDBALRepository;
use Doctrine\DBAL\Connection;
use ValueObjects\StringLiteral\StringLiteral;

class SkippedSimilarEventsRepository extends AbstractDBALRepository
{
    public function __construct(Connection $connection)
    {
        parent::__construct($connection, new StringLiteral('skipped_similar_events'));
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
