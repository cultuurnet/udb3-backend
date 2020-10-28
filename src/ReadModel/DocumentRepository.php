<?php

namespace CultuurNet\UDB3\ReadModel;

use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;

interface DocumentRepository
{
    /**
     * @throws DocumentDoesNotExistException
     */
    public function fetch(string $id, bool $includeMetadata = false): JsonDocument;

    /**
     * @deprecated use DocumentRepository::fetch() instead for easier error handling in case the document does not
     *   exist.
     * @throws DocumentGoneException
     */
    public function get(string $id, bool $includeMetadata = false): ?JsonDocument;

    public function save(JsonDocument $readModel): void;

    public function remove($id): void;
}
