<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use Psr\Http\Message\ServerRequestInterface;

final class JsonSchemaValidatingRequestBodyParser implements RequestBodyParser
{
    private const MAX_ERRORS = 100;

    private RequestBodyParser $baseParser;
    private Validator $validator;
    private string $jsonSchema;

    public function __construct(string $jsonSchema)
    {
        $this->jsonSchema = $jsonSchema;
        $this->baseParser = new JsonRequestBodyParser();
        $this->validator = new Validator(null, self::MAX_ERRORS);
    }

    public function parse(ServerRequestInterface $request)
    {
        $data = $this->baseParser->parse($request);

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

        return $data;
    }
}
