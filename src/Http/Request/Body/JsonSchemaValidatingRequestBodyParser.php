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
    private const SCHEMA_LOCATIONS = __DIR__ . '/../../../vendor/publiq/stoplight-docs-uitdatabank/models/';

    private RequestBodyParser $baseParser;
    private Validator $validator;
    private string $jsonSchema;

    private function __construct(string $jsonSchema, RequestBodyParser $baseParser)
    {
        $this->jsonSchema = $jsonSchema;
        $this->baseParser = $baseParser;
        $this->validator = new Validator(null, 100);
    }

    public static function forStoplightModelFileName(
        string $stoplightModelFileName,
        ?RequestBodyParser $baseParser = null
    ): self
    {
        $baseParser = $baseParser ?? new JsonRequestBodyParser();
        $stoplightModelFileName = ltrim($stoplightModelFileName, '/');
        $schema = file_get_contents(self::SCHEMA_LOCATIONS . $stoplightModelFileName);
        return new self($schema, $baseParser);
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
