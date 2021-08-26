<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use DateTimeImmutable;
use Psr\Http\Message\ServerRequestInterface;

final class UpdateCalendarValidationRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $errors = [];
        try {
            $baseValidationParser = new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::EVENT_CALENDAR_PUT);
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

        $calendarType = $data->calendarType;
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
                    $this->validateDateRange($data),
                    $this->validateOpeningHoursTimeRanges($data)
                );
                break;

            case 'permanent':
                $errors = array_merge(
                    $errors,
                    $this->validateOpeningHoursTimeRanges($data)
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
        foreach ($data->subEvent as $key => $subEvent) {
            if (is_object($subEvent)) {
                $errors[] = $this->validateDateRange($subEvent, '/subEvent/' . $key);
            }
        }
        return array_merge(...$errors);
    }

    /**
     * @return SchemaError[]
     */
    private function validateDateRange(object $data, string $jsonPointer = ''): array
    {
        if (!isset($data->startDate, $data->endDate) || !is_string($data->startDate) || !is_string($data->endDate)) {
            // Error(s) will be reported by the Schema validation.
            return [];
        }

        $startDate = DateTimeImmutable::createFromFormat(DATE_ATOM, $data->startDate);
        $endDate = DateTimeImmutable::createFromFormat(DATE_ATOM, $data->endDate);
        if ($startDate === false || $endDate === false) {
            // Error(s) will be reported by the Schema validation.
            return [];
        }

        if ($startDate > $endDate) {
            return [new SchemaError($jsonPointer . '/endDate', 'endDate should not be before startDate')];
        }
        return [];
    }

    /**
     * @return SchemaError[]
     */
    private function validateOpeningHoursTimeRanges(object $data): array
    {
        if (!isset($data->openingHours) || !is_array($data->openingHours)) {
            // Error(s) will be reported by the Schema validation.
            return [];
        }

        $errors = [];
        foreach ($data->openingHours as $index => $openingHourData) {
            if (!isset($openingHourData->opens, $openingHourData->closes) ||
                !is_string($openingHourData->opens) ||
                !is_string($openingHourData->closes)) {
                // Error(s) will be reported by the Schema validation.
                continue;
            }

            $opens = DateTimeImmutable::createFromFormat('H:i', $openingHourData->opens);
            $closes = DateTimeImmutable::createFromFormat('H:i', $openingHourData->opens);

            if ($opens !== false && $closes !== false && $opens > $closes) {
                $errors[] = new SchemaError( '/openingHours/' . $index . '/closes', 'closes should not be before opens');
            }
        }
        return $errors;
    }
}
