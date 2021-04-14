<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\ReadModel;

interface DocumentRepository
{
    /**
     * @throws DocumentDoesNotExist
     */
    public function fetch(string $id, bool $includeMetadata = false): JsonDocument;

    public function save(JsonDocument $readModel): void;

    public function remove($id): void;
}
