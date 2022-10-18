<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

final class BookingInfoValidatingRequestBodyParser implements RequestBodyParser
{
    private string $propertyName;

    public function __construct(string $propertyName = '')
    {
        $this->propertyName = $propertyName;
    }

    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();

        $objectToValidate = $data;
        if (!empty($this->propertyName)) {
            $objectToValidate = $data->{$this->propertyName} ?? new stdClass();
        }

        $errors = (new DateRangeValidator())->validate(
            $objectToValidate,
            $this->propertyName,
            'availabilityStarts',
            'availabilityEnds'
        );

        if (count($errors) > 0) {
            throw ApiProblem::bodyInvalidData(...$errors);
        }

        return $request;
    }
}
