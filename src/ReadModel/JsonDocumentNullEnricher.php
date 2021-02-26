<?php

namespace CultuurNet\UDB3\ReadModel;

use Broadway\Domain\Metadata;

class JsonDocumentNullEnricher implements JsonDocumentMetaDataEnricherInterface
{
    /**
     * @return JsonDocument
     */
    public function enrich(JsonDocument $jsonDocument, Metadata $metadata)
    {
        return $jsonDocument;
    }
}
