<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Offer\ChildcareTimeValidator;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;

class ChildcareTimeValidatingRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $subEvents = $request->getParsedBody();

        if (!is_array($subEvents)) {
            return $request;
        }

        $errors = [];
        $childcareTimeValidator = new ChildcareTimeValidator();
        foreach ($subEvents as $key => $subEvent) {
            if (is_object($subEvent)) {
                $errors[] = $childcareTimeValidator->validate($subEvent, '/' . $key);
            }
        }

        $errors = array_merge(...$errors);

        if (count($errors) > 0) {
            throw ApiProblem::bodyInvalidData(...$errors);
        }

        return $request;
    }
}
