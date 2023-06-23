<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\ReadModel;

interface DocumentRepository
{
    /**
     * @throws DocumentDoesNotExist
     */
    public function fetch(string $id, bool $includeMetadata = false): JsonDocument;

    public function save(JsonDocument $document): void;

    public function remove(string $id): void;
}
