<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Offer\DateRangeValidator;
use CultuurNet\UDB3\Http\Offer\OpeningHoursRangeValidator;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;

final class CalendarValidationRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $errors = [];

        $data = $request->getParsedBody();

        if (!is_object($data)) {
            // If the body data is not an object, there's nothing left to validate. Just re-throw the errors from the
            // JSON schema validation.
            throw ApiProblem::bodyInvalidData(...$errors);
        }

        $calendarType = $data->calendarType ?? null;
        switch ($calendarType) {
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
}
