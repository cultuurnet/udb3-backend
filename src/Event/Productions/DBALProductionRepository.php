<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

use Cake\Chronos\Chronos;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Label\ReadModels\Doctrine\AbstractDBALRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

class DBALProductionRepository extends AbstractDBALRepository implements ProductionRepository
{
    public const TABLE_NAME = 'productions';

    public function __construct(Connection $connection)
    {
        parent::__construct($connection, self::TABLE_NAME);
    }

    public function add(Production $production): void
    {
        foreach ($production->getEventIds() as $eventId) {
            $this->addEvent($eventId, $production);
        }
    }

    public function find(ProductionId $productionId): Production
    {
        $results = $this->getConnection()->fetchAllAssociative(
            'SELECT * FROM productions WHERE production_id = :productionId',
            [
                'productionId' => $productionId->toNative(),
            ]
        );

        if (!$results) {
            throw new EntityNotFoundException('No production found for id ' . $productionId->toNative());
        }

        $production = new Production(
            $productionId,
            $results[0]['name'],
            []
        );

        foreach ($results as $result) {
            $production = $production->addEvent($result['event_id']);
        }

        return $production;
    }

    public function addEvent(string $eventId, Production $production): void
    {
        $addedAt = Chronos::now();
        $this->getConnection()->insert(
            $this->getTableName(),
            [
                'event_id' => $eventId,
                'production_id' => $production->getProductionId()->toNative(),
                'name' => $production->getName(),
                'added_at' => $addedAt->format('Y-m-d'),
            ]
        );
    }

    public function removeEvent(string $eventId, ProductionId $productionId): void
    {
        $this->getConnection()->delete(
            $this->getTableName(),
            [
                'event_id' => $eventId,
                'production_id' => $productionId->toNative(),
            ]
        );
    }

    /** @param string[] $eventIds */
    public function removeEvents(array $eventIds, ProductionId $productionId): void
    {
        foreach ($eventIds as $eventId) {
            $this->removeEvent($eventId, $productionId);
        }
    }

    public function moveEvents(ProductionId $from, Production $to): void
    {
        $addedAt = Chronos::now();
        $this->getConnection()->update(
            $this->getTableName(),
            [
                'production_id' => $to->getProductionId()->toNative(),
                'name' => $to->getName(),
                'added_at' => $addedAt->format('Y-m-d'),
            ],
            [
                'production_id' => $from->toNative(),
            ]
        );
    }

    public function renameProduction(ProductionId $productionId, string $name): void
    {
        $this->getConnection()->update(
            $this->getTableName(),
            [
                'name' => $name,
            ],
            [
                'production_id' => $productionId->toNative(),
            ]
        );
    }

    /**
     * @return Production[]
     */
    public function search(string $keyword, int $start, int $limit): array
    {
        $keyword = $this->addWildcardToKeyword($keyword);
        $subQuery = $this->createSearchQuery($keyword)
            ->setFirstResult($start)
            ->setMaxResults($limit);

        $query = $this->getConnection()->createQueryBuilder()
            ->select('p1.production_id, p1.name, p1.event_id')
            ->from($this->getTableName(), 'p1')
            ->innerJoin('p1', sprintf('(%s)', $subQuery->getSQL()), 'p2', 'p1.production_id = p2.production_id');

        if (!empty($keyword)) {
            $query->setParameter(':keyword', $keyword);
        }

        $results = $query->execute()->fetchAllAssociative();

        if (empty($results)) {
            return [];
        }

        /** @var Production[] $productions */
        $productions = [];
        foreach ($results as $result) {
            $productionId = $result['production_id'];

            if (empty($productions[$productionId])) {
                $productions[$productionId] = new Production(
                    ProductionId::fromNative($productionId),
                    $result['name'],
                    [$result['event_id']]
                );
                continue;
            }

            $productions[$productionId] = $productions[$productionId]->addEvent($result['event_id']);
        }

        return array_values($productions);
    }

    public function count(string $keyword): int
    {
        $keyword = $this->addWildcardToKeyword($keyword);
        return count($this->createSearchQuery($keyword)->execute()->fetchAllAssociative());
    }

    private function createSearchQuery(string $keyword): QueryBuilder
    {
        $query = $this->getConnection()->createQueryBuilder()
            ->select('DISTINCT production_id')
            ->from($this->getTableName());

        if (!empty($keyword)) {
            $query = $query->where('MATCH(name) AGAINST(:keyword IN BOOLEAN MODE)')
                ->setParameter(':keyword', $keyword);
        }

        return $query;
    }

    public function addWildcardToKeyword(string $keyword): string
    {
        if (empty($keyword)) {
            return '';
        }

        // return keyword as is for keywords ending in non-alphanumeric characters
        if (!ctype_alnum(substr($keyword, -1))) {
            return $keyword;
        }

        return $keyword . '*';
    }

    public function findProductionForEventId(string $eventId): Production
    {
        $results = $this->getConnection()->fetchAllAssociative(
            'SELECT * FROM productions WHERE production_id = (SELECT production_id FROM productions WHERE event_id = :eventId)',
            [
                'eventId' => $eventId,
            ]
        );

        if (!$results) {
            throw new EntityNotFoundException('No production found for event with id ' . $eventId);
        }

        $production = new Production(
            ProductionId::FromNative($results[0]['production_id']),
            $results[0]['name'],
            []
        );

        foreach ($results as $result) {
            $production = $production->addEvent($result['event_id']);
        }

        return $production;
    }
}
