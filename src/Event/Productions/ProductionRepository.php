<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\EntityNotFoundException;

interface ProductionRepository
{
    public function add(Production $production): void;

    /**
     * @throws EntityNotFoundException
     */
    public function find(ProductionId $productionId): Production;

    public function addEvent(string $eventId, Production $production): void;

    public function removeEvent(string $eventId, ProductionId $productionId): void;

    /** @param string[] $eventIds */
    public function removeEvents(array $eventIds, ProductionId $productionId): void;

    public function moveEvents(ProductionId $from, Production $to): void;

    public function renameProduction(ProductionId $productionId, string $name): void;

    /**
     * @return Production[]
     */
    public function search(string $keyword, int $start, int $limit): array;

    public function count(string $keyword): int;

    /**
     * @throws EntityNotFoundException
     */
    public function findProductionForEventId(string $eventId): Production;
}
