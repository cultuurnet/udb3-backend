<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class JsonSchemaValidatingRequestBodyParser implements RequestBodyParser
{
    use RequestBodyParserNextTrait;

    private const MAX_ERRORS = 100;

    private Validator $validator;
    private string $jsonSchema;

    public function __construct(string $jsonSchema)
    {
        $this->jsonSchema = $jsonSchema;
        $this->validator = new Validator(null, self::MAX_ERRORS);
    }

    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();
        if ($data === null) {
            throw new RuntimeException('Given ServerRequestInterface has no parsed body.');
        }

        $result = $this->validator->validate($data, $this->jsonSchema);

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

        return $this->callNextParser($request);
    }
}
