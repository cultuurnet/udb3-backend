<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\ApiProblem;

final class SchemaError
{
    private string $jsonPointer;
    private string $error;

    public function __construct(string $jsonPointer, string $error)
    {
        $this->jsonPointer = $jsonPointer;
        $this->error = $error;
    }

    public function getJsonPointer(): string
    {
        return $this->jsonPointer;
    }

    public function getError(): string
    {
        return $this->error;
    }
}
