<?php

namespace CultuurNet\UDB3\ReadModel;

use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;

interface DocumentRepository
{
    /**
     * @throws DocumentGoneException
     */
    public function get(string $id, bool $includeMetadata = false): ?JsonDocument;

    public function save(JsonDocument $readModel): void;

    public function remove($id): void;
}
