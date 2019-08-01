<?php

namespace CultuurNet\UDB3\Model\Import;

use CultuurNet\UDB3\ReadModel\JsonDocument;

class DecodedDocument
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var array
     */
    private $body;

    /**
     * @param string $id
     * @param array $body
     */
    public function __construct($id, array $body)
    {
        $this->id = (string) $id;
        $this->body = $body;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param array $body
     * @return static
     */
    public function withBody(array $body)
    {
        $c = clone $this;
        $c->body = $body;
        return $c;
    }

    /**
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->body, JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return JsonDocument
     */
    public function toJsonDocument()
    {
        return new JsonDocument($this->id, $this->toJson());
    }

    /**
     * @param string $id
     * @param string $json
     * @return self
     */
    public static function fromJson($id, $json)
    {
        $body = json_decode($json, true);

        if (is_null($body)) {
            throw new \InvalidArgumentException('The given JSON is not valid and can not be decoded.');
        }

        return new self($id, $body);
    }

    /**
     * @param JsonDocument $jsonDocument
     * @return static
     */
    public static function fromJsonDocument(JsonDocument $jsonDocument)
    {
        return static::fromJson($jsonDocument->getId(), $jsonDocument->getRawBody());
    }
}
