<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use JsonException;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use Psr\Http\Message\ServerRequestInterface;

final class JsonRequestBodyParser implements RequestBodyParser
{
    private ?string $jsonSchema;

    public function __construct(?string $jsonSchema = null)
    {
        $this->jsonSchema = $jsonSchema;
    }

    public function parse(ServerRequestInterface $request)
    {
        $body = (string) $request->getBody();
        if ($body === '') {
            throw ApiProblem::bodyMissing();
        }

        try {
            $decoded = json_decode($body, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw ApiProblem::bodyInvalidSyntax('JSON');
        }

        $this->validateSchema($decoded);

        return $decoded;
    }

    /**
     * @throws ApiProblem
     */
    private function validateSchema($decoded): void
    {
        if ($this->jsonSchema === null) {
            return;
        }

        $validator = new Validator(null, 100);
        $result = $validator->validate($decoded, $this->jsonSchema);

        if (!$result->isValid()) {
            $errors = (new ErrorFormatter())->format($result->error());
            $schemaErrors = [];
            foreach ($errors as $jsonPointer => $errorsPerPointer) {
                foreach ($errorsPerPointer as $error) {
                    $schemaErrors[] = new SchemaError($jsonPointer, $error);
                }
            }
            throw ApiProblem::bodyInvalidData(...$schemaErrors);
        }
    }
}
