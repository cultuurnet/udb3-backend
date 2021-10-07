<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\ReadModel;

use Broadway\ReadModel\Identifiable;
use CultuurNet\UDB3\Json;
use stdClass;

final class JsonDocument implements Identifiable
{
    private $id;
    private $body;

    public function __construct($id, $rawBody = '{}')
    {
        $this->id = $id;
        $this->body = $rawBody;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getBody(): stdClass
    {
        return (object) Json::decode($this->body);
    }

    public function getAssocBody(): array
    {
        return (array) Json::decodeAssociatively($this->body);
    }

    public function getRawBody(): string
    {
        return $this->body;
    }

    public function withBody(stdClass $body): self
    {
        return new self($this->id, Json::encode($body));
    }

    public function withAssocBody(array $body): JsonDocument
    {
        return new self($this->id, Json::encode($body));
    }

    public function apply(callable $fn): self
    {
        $body = $fn($this->getBody());
        return $this->withBody($body);
    }

    public function applyAssoc(callable $fn): JsonDocument
    {
        $body = $fn($this->getAssocBody());
        return $this->withAssocBody($body);
    }
}
