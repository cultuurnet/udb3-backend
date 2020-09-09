<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\Label\ReadModels\Doctrine\AbstractDBALRepository;
use Doctrine\DBAL\Connection;
use ValueObjects\StringLiteral\StringLiteral;

final class SimilarEventsRepository extends AbstractDBALRepository
{
    public function __construct(Connection $connection)
    {
        parent::__construct($connection, new StringLiteral('similar_events'));
    }

    public function add(Suggestion $suggestion): void
    {
        $this->getConnection()->insert(
            $this->getTableName()->toNative(),
            [
                'similarity' => $suggestion->getSimilarity(),
                'event1' => $suggestion->getEventOne(),
                'event2' => $suggestion->getEventTwo(),
            ]
        );
    }

    public function findNextSuggestion(): Suggestion
    {
        $query = $this->getConnection()->createQueryBuilder()
            ->select('se.similarity, se.event1, se.event2, p1.production_id as production1, p2.production_id as production2')
            ->from($this->getTableName()->toNative(), 'se')
            ->leftJoin('se', ProductionRepository::TABLE_NAME, 'p1', 'p1.event_id = se.event1')
            ->leftJoin('se', ProductionRepository::TABLE_NAME, 'p2', 'p2.event_id = se.event2')
            ->where('p1.production_id IS NULL')
            ->orWhere('p2.production_id IS NULL')
            ->orWhere('p1.production_id != p2.production_id')
            ->orderBy('similarity', 'DESC');
        $results = $query->execute();
        $result = $results->fetch();

        if (!$result) {
            throw new SuggestionsNotFound();
        }

        return new Suggestion(
            (string) $result['event1'],
            (string) $result['event2'],
            (float) $result['similarity']
        );
    }
}
