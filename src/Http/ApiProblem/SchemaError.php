<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\ApiProblem;

final class SchemaError
{
    private string $jsonPointer;
    private string $error;
    private ?string $errorType;

    public function __construct(string $jsonPointer, string $error, string $errorType = null)
    {
        $this->jsonPointer = $jsonPointer;
        $this->error = $error;
        $this->errorType = $errorType;
    }

    public function getJsonPointer(): string
    {
        return $this->jsonPointer;
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function getErrorType(): ?string
    {
        return $this->errorType;
    }
}
