<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;

final class CalendarValidationRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $errors = [];

        $data = $request->getParsedBody();

        if (!is_object($data)) {
            return $request;
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
