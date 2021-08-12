<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
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
        $validator = new Validator(null, 1);
        $result = $validator->validate($data, file_get_contents(__DIR__ . '/UpdateSubEventsSchema.json'));

        if (!$result->isValid()) {
            $errors = (new ErrorFormatter())->format($result->error());
            $jsonPointers = array_keys($errors);
            $jsonPointer = $jsonPointers[0];
            $detail = $errors[$jsonPointer][0];
            throw ApiProblem::bodyInvalidData($detail, $jsonPointer);
        }
    }
}
