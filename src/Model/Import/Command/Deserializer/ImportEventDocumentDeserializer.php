<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Command\Deserializer;

use CultuurNet\UDB3\Model\Import\Command\ImportEventDocument;

class ImportEventDocumentDeserializer extends ImportDocumentDeserializer
{
    /**
     * @inheritdoc
     */
    protected function createCommand($id, $url, $jwt, $apiKey = null)
    {
        return new ImportEventDocument($id, $url, $jwt, $apiKey);
    }
}
