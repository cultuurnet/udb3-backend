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
}
