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

    public function __construct(string $id, array $body)
    {
        $this->id = $id;
        $this->body = $body;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getBody(): array
    {
        return $this->body;
    }

    public function withBody(array $body): self
    {
        $c = clone $this;
        $c->body = $body;
        return $c;
    }

    public function toJson(): string
    {
        return json_encode($this->body, JSON_UNESCAPED_SLASHES);
    }

    public function toJsonDocument(): JsonDocument
    {
        return new JsonDocument($this->id, $this->toJson());
    }

    public static function fromJson(string $id, string $json): self
    {
        $body = json_decode($json, true);

        if (is_null($body)) {
            throw new \InvalidArgumentException('The given JSON is not valid and can not be decoded.');
        }

        return new self($id, $body);
    }

    public static function fromJsonDocument(JsonDocument $jsonDocument): self
    {
        return static::fromJson($jsonDocument->getId(), $jsonDocument->getRawBody());
    }
}
