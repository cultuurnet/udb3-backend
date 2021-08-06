<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use Exception;

final class RequestBodyInvalidData extends Exception
{
    /**
     * @var string
     */
    private $jsonPointer;

    public function __construct(string $detail, string $jsonPointer)
    {
        parent::__construct($detail, 400);
        $this->jsonPointer = $jsonPointer;
    }

    public function getJsonPointer(): string
    {
        return $this->jsonPointer;
    }

    public static function requiredPropertyNotFound(string $jsonPointer): self
    {
        $propertyParts = explode('/', $jsonPointer);
        $property = end($propertyParts);

        return new self(
            'Required property "' . $property . '" not found.',
            $jsonPointer
        );
    }
}
