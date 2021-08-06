<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailability;
use InvalidArgumentException;

final class UpdateBookingAvailabilityValidator
{
    public function validate(array $data): void
    {
        $messages = $this->validateType($data);

        if (!empty($messages)) {
            $exception = new DataValidationException();
            $exception->setValidationMessages($messages);
            throw $exception;
        }
    }

    private function validateType(array $data): array
    {
        if (!isset($data['type'])) {
            return [
                'type' => 'Required but could not be found',
            ];
        }

        try {
            BookingAvailability::fromNative($data['type']);
        } catch (InvalidArgumentException $e) {
            return [
                'type' => 'Invalid type provided',
            ];
        }

        return [];
    }
}
