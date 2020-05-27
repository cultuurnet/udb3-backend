<?php

namespace CultuurNet\UDB3\ReadModel;

use Broadway\Domain\Metadata;

interface JsonDocumentMetaDataEnricherInterface
{
    /**
     * @param JsonDocument $jsonDocument
     * @param Metadata $metadata
     * @return JsonDocument
     */
    public function enrich(JsonDocument $jsonDocument, Metadata $metadata);
}
