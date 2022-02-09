<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Offer\DateRangeValidator;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use DateTimeImmutable;
use Psr\Http\Message\ServerRequestInterface;

final class CalendarValidationRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $errors = [];

        $data = $request->getParsedBody();

        $calendarType = $data->calendarType ?? null;
        switch ($calendarType) {
            case 'periodic':
                $errors = array_merge(
                    $errors,
                    (new DateRangeValidator())->validate($data),
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
            $closes = DateTimeImmutable::createFromFormat('H:i', $openingHourData->closes);

            if ($opens !== false && $closes !== false && $opens > $closes) {
                $errors[] = new SchemaError('/openingHours/' . $index . '/closes', 'closes should not be before opens');
            }
        }
        return $errors;
    }
}
