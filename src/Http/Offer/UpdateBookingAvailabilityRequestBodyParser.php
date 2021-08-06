<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\Request\Body\ContentMediationRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyInvalidData;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailability;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

final class UpdateBookingAvailabilityRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): array
    {
        $data = (new ContentMediationRequestBodyParser())->parse($request);
        $this->validateType($data);
        return $data;
    }

    private function validateType(array $data): void
    {
        if (!isset($data['type'])) {
            throw RequestBodyInvalidData::requiredPropertyNotFound('/type');
        }

        try {
            BookingAvailability::fromNative($data['type']);
        } catch (InvalidArgumentException $e) {
            throw new RequestBodyInvalidData('Invalid type provided.', '/type');
        }
    }
}
