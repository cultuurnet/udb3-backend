<?php

namespace CultuurNet\UDB3\ReadModel;

use Broadway\ReadModel\ReadModelInterface;
use stdClass;

final class JsonDocument implements ReadModelInterface
{
    protected $id;
    protected $body;

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
        return (object) json_decode($this->body);
    }

    public function getAssocBody(): array
    {
        return json_decode($this->body, true);
    }

    public function getRawBody(): string
    {
        return $this->body;
    }

    public function withBody(stdClass $body): self
    {
        return new self($this->id, json_encode($body));
    }

    public function withAssocBody(array $body): JsonDocument
    {
        return new self($this->id, json_encode($body));
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
