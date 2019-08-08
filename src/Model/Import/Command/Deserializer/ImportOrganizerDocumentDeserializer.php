<?php

namespace CultuurNet\UDB3\Model\Import\Command\Deserializer;

use CultuurNet\UDB3\Model\Import\Command\ImportOrganizerDocument;

class ImportOrganizerDocumentDeserializer extends ImportDocumentDeserializer
{
    /**
     * @inheritdoc
     */
    protected function createCommand($id, $url, $jwt, $apiKey = null)
    {
        return new ImportOrganizerDocument($id, $url, $jwt, $apiKey);
    }
}
