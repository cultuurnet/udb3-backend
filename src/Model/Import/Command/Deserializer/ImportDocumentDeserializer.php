<?php

namespace CultuurNet\UDB3\Model\Import\Command\Deserializer;

use CultuurNet\Deserializer\JSONDeserializer;
use CultuurNet\Deserializer\MissingValueException;
use CultuurNet\UDB3\Model\Import\Command\ImportDocument;
use ValueObjects\StringLiteral\StringLiteral;

abstract class ImportDocumentDeserializer extends JSONDeserializer
{
    public function deserialize(StringLiteral $json)
    {
        $json = parent::deserialize($json);

        if (!isset($json->id)) {
            throw new MissingValueException('id is missing');
        }

        if (!isset($json->url)) {
            throw new MissingValueException('url is missing');
        }

        if (!isset($json->jwt)) {
            throw new MissingValueException('jwt is missing');
        }

        return $this->createCommand(
            $json->id,
            $json->url,
            $json->jwt,
            isset($json->apiKey) ? $json->apiKey : null
        );
    }

    /**
     * @param string $id
     * @param string $url
     * @param string $jwt
     * @param string|null $apiKey
     * @return ImportDocument
     */
    abstract protected function createCommand(
        $id,
        $url,
        $jwt,
        $apiKey = null
    );
}
