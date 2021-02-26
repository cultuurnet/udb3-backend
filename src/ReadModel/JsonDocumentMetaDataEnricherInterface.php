<?php

namespace CultuurNet\UDB3\ReadModel;

use Broadway\Domain\Metadata;

interface JsonDocumentMetaDataEnricherInterface
{
    /**
     * @return JsonDocument
     */
    public function enrich(JsonDocument $jsonDocument, Metadata $metadata);
}
