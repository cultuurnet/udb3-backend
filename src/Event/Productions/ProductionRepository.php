<?php

namespace CultuurNet\UDB3\Event\Productions;

use Cake\Chronos\Chronos;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Label\ReadModels\Doctrine\AbstractDBALRepository;
use Doctrine\DBAL\Connection;
use ValueObjects\StringLiteral\StringLiteral;

class ProductionRepository extends AbstractDBALRepository
{
    public function __construct(Connection $connection)
    {
        parent::__construct($connection, new StringLiteral('productions'));
    }

    public function add(Production $production)
    {
        foreach ($production->getEventIds() as $eventId) {
            $this->addEvent($eventId, $production);
        }
    }

    public function find(ProductionId $productionId)
    {
        $results = $this->getConnection()->fetchAll(
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

    public function addEvent(string $eventId, Production $production)
    {
        $addedAt = Chronos::now();
        $this->getConnection()->insert(
            $this->getTableName()->toNative(),
            [
                'event_id' => $eventId,
                'production_id' => $production->getProductionId()->toNative(),
                'name' => $production->getName(),
                'added_at' => $addedAt->format(DATE_ATOM),
            ]
        );
    }

    public function removeEvent(string $eventId, ProductionId $productionId)
    {
        $this->getConnection()->delete(
            $this->getTableName()->toNative(),
            [
                'event_id' => $eventId,
                'production_id' => $productionId->toNative(),
            ]
        );
    }

    public function moveEvents(ProductionId $from, Production $to)
    {
        $addedAt = Chronos::now();
        $this->getConnection()->update(
            $this->getTableName()->toNative(),
            [
                'production_id' => $to->getProductionId()->toNative(),
                'name' => $to->getName(),
                'added_at' => $addedAt->format(DATE_ATOM),
            ],
            [
                'production_id' => $from->toNative(),
            ]
        );
    }

    /**
     * @param string $keyword
     * @return Production[]
     */
    public function search(string $keyword): array
    {
        $sql = 'SELECT production_id, name, GROUP_CONCAT(event_id) as events
                FROM ' . $this->getTableName()->toNative() . ' 
                
                GROUP BY production_id';

        $results = $this->getConnection()->executeQuery(
            $sql,
            [
                'keyword' => $keyword,
            ]
        )->fetchAll();

        if (empty($results)) {
            return [];
        }

        return array_map(
            function (array $data) {
                return new Production(
                    ProductionId::fromNative($data['production_id']),
                    $data['name'],
                    explode(',', $data['events'])
                );
            },
            $results
        );
    }
}
