<?php

namespace CultuurNet\UDB3\Model\Import\Command\Deserializer;

use CultuurNet\UDB3\Model\Import\Command\ImportPlaceDocument;

class ImportPlaceDocumentDeserializer extends ImportDocumentDeserializer
{
    /**
     * @inheritdoc
     */
    protected function createCommand($id, $url, $jwt, $apiKey = null)
    {
        return new ImportPlaceDocument($id, $url, $jwt, $apiKey);
    }
}
