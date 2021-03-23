<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\EntityNotFoundException;

interface ProductionRepository
{
    public function add(Production $production): void;

    public function find(ProductionId $productionId): Production;

    public function addEvent(string $eventId, Production $production): void;

    public function removeEvent(string $eventId, ProductionId $productionId): void;

    public function moveEvents(ProductionId $from, Production $to): void;

    /**
     * @return Production[]
     */
    public function search(string $keyword, int $start, int $limit): array;

    public function count(string $keyword): int;

    public function findProductionForEventId(string $eventId): Production;

    /**
     * @return SimilarEventPair[]
     * @throws EntityNotFoundException
     */
    public function findEventPairs(string $forEventId, ProductionId $inProductionId): array;
}
