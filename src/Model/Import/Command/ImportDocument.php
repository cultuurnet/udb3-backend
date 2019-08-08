<?php

namespace CultuurNet\UDB3\Model\Import\Command;

abstract class ImportDocument
{
    /**
     * @var string
     */
    private $documentId;

    /**
     * @var string
     */
    private $documentUrl;

    /**
     * @var string
     */
    private $jwt;

    /**
     * @var string|null
     */
    private $apiKey;

    /**
     * @param string $documentId
     * @param string $documentUrl
     * @param string $jwt
     * @param string|null $apiKey
     */
    public function __construct($documentId, $documentUrl, $jwt, $apiKey = null)
    {
        $this->documentId = (string) $documentId;
        $this->documentUrl = (string) $documentUrl;
        $this->jwt = (string) $jwt;
        $this->apiKey = $apiKey ? (string) $apiKey : $apiKey;
    }

    /**
     * @return string
     */
    public function getDocumentId()
    {
        return $this->documentId;
    }

    /**
     * @return string
     */
    public function getDocumentUrl()
    {
        return $this->documentUrl;
    }

    /**
     * @return string
     */
    public function getJwt()
    {
        return $this->jwt;
    }

    /**
     * @return string|null
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }
}
