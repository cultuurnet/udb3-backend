<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use InvalidArgumentException;
use JsonException;

class DecodedDocument
{
    private string $id;

    private array $body;

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
        try {
            $body = Json::decodeAssociatively($json);
        } catch (JsonException $e) {
            throw new InvalidArgumentException('The given JSON is not valid and can not be decoded.');
        }

        return new self($id, $body);
    }

    public static function fromJsonDocument(JsonDocument $jsonDocument): self
    {
        return static::fromJson($jsonDocument->getId(), $jsonDocument->getRawBody());
    }
}
