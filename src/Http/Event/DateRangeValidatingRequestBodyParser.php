<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Offer\DateRangeValidator;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;

class DateRangeValidatingRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $subEvents = $request->getParsedBody();

        $errors = [];
        $dateRangeValidator = new DateRangeValidator();
        foreach ($subEvents as $key => $subEvent) {
            if (is_object($subEvent)) {
                $errors[] = $dateRangeValidator->validate($subEvent, '/' . $key);
            }
        }

        $errors = array_merge(...$errors);

        if (count($errors) > 0) {
            throw ApiProblem::bodyInvalidData(...$errors);
        }

        return $request;
    }
}
