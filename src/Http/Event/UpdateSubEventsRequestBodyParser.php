<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Body\ContentNegotiationRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use Psr\Http\Message\ServerRequestInterface;

final class UpdateSubEventsRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request)
    {
        $data = (new ContentNegotiationRequestBodyParser())->parse($request);
        $this->validateSchema($data);
        return $data;
    }

    /**
     * @throws ApiProblem
     */
    private function validateSchema($data): void
    {
        $validator = new Validator(null, 100);
        $result = $validator->validate($data, file_get_contents(__DIR__ . '/UpdateSubEventsSchema.json'));

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
