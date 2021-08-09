<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\Body\ContentNegotiationRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailability;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

final class UpdateBookingAvailabilityRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): array
    {
        $data = (new ContentNegotiationRequestBodyParser())->parse($request);
        $this->validateType($data);
        return $data;
    }

    private function validateType(array $data): void
    {
        if (!isset($data['type'])) {
            throw ApiProblem::bodyInvalidData('Required property "type" not found.', '/type')
                ->toException();
        }

        try {
            BookingAvailability::fromNative($data['type']);
        } catch (InvalidArgumentException $e) {
            throw ApiProblem::bodyInvalidData('Invalid type provided.', '/type')
                ->toException();
        }
    }
}
