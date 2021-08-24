<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Parsers\SchemaParser;
use Opis\JsonSchema\Resolvers\SchemaResolver;
use Opis\JsonSchema\SchemaLoader;
use Opis\JsonSchema\Validator;
use Opis\Uri\Uri;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class JsonSchemaValidatingRequestBodyParser implements RequestBodyParser
{
    private const MAX_ERRORS = 100;

    private Validator $validator;
    private Uri $jsonSchema;

    private function __construct(SchemaResolver $schemaResolver, Uri $jsonSchema)
    {
        $this->jsonSchema = $jsonSchema;

        $this->validator = new Validator(
            new SchemaLoader(
                new SchemaParser(),
                $schemaResolver
            ),
            self::MAX_ERRORS
        );
    }

    /**
     * Uses JsonSchemaLocator::loadSchema() to load the schema from the given filename.
     * Filename must be one of the JsonSchemaLocator constants!
     */
    public static function fromFile(string $jsonSchemaLocatorFile): self
    {
        return new self(
            JsonSchemaLocator::createSchemaResolver(),
            JsonSchemaLocator::createSchemaUri($jsonSchemaLocatorFile)
        );
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

        return $request;
    }
}
