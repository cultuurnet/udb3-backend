<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;

final class UpdateCalendarValidatingRequestBodyParser implements RequestBodyParser
{
    private string $jsonSchemaLocatorFile;

    public function __construct(string $jsonSchemaLocatorFile)
    {
        $this->jsonSchemaLocatorFile = $jsonSchemaLocatorFile;
    }

    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $errors = [];
        try {
            $baseValidationParser = new JsonSchemaValidatingRequestBodyParser($this->jsonSchemaLocatorFile);
            $request = $baseValidationParser->parse($request);
        } catch (ApiProblem $apiProblem) {
            // Re-throw anything that's not https://api.publiq.be/probs/body/invalid-data.
            if ($apiProblem->getType() !== ApiProblem::bodyInvalidData()->getType()) {
                throw $apiProblem;
            }
            $errors = $apiProblem->getSchemaErrors();
        }

        $data = $request->getParsedBody();

        if (!is_object($data)) {
            // If the body data is not an object, there's nothing left to validate. Just re-throw the errors from the
            // JSON schema validation.
            throw ApiProblem::bodyInvalidData(...$errors);
        }

        $calendarType = $data->calendarType ?? null;
        switch ($calendarType) {
            case 'single':
            case 'multiple':
                $errors = array_merge(
                    $errors,
                    $this->validateSubEventDateRanges($data)
                );
                break;

            case 'periodic':
                $errors = array_merge(
                    $errors,
                    (new DateRangeValidator())->validate($data),
                    (new OpeningHoursRangeValidator())->validate($data)
                );
                break;

            case 'permanent':
                $errors = array_merge(
                    $errors,
                    (new OpeningHoursRangeValidator())->validate($data)
                );
                break;

            default:
                break;
        }

        if (count($errors) > 0) {
            throw ApiProblem::bodyInvalidData(...$errors);
        }

        return $request;
    }

    /**
     * @return SchemaError[]
     */
    private function validateSubEventDateRanges(object $data): array
    {
        if (!isset($data->subEvent) || !is_array($data->subEvent)) {
            // Error will be reported by the Schema validation.
            return [];
        }

        $errors = [];
        $dateRangeValidator = new DateRangeValidator();
        foreach ($data->subEvent as $key => $subEvent) {
            if (is_object($subEvent)) {
                $errors[] = $dateRangeValidator->validate($subEvent, '/subEvent/' . $key);
            }
        }
        return array_merge(...$errors);
    }
}
