<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;
use InvalidArgumentException;

class UpdateStatusValidator implements DataValidatorInterface
{
    public function validate(array $data): void
    {
        $messages = [];
        $messages = array_merge($messages, $this->validateType($data));
        $messages = array_merge($messages, $this->validateReason($data));

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
            StatusType::fromNative($data['type']);
        } catch (InvalidArgumentException $e) {
            return [
                'type' => 'Invalid type provided',
            ];
        }

        return [];
    }

    private function validateReason(array $data): array
    {
        if (!isset($data['reason'])) {
            return [];
        }

        if (!is_array($data['reason'])) {
            return [
                'reason' => 'Should be an object with language codes as properties and string values',
            ];
        }

        $messages = [];
        foreach ($data['reason'] as $language => $translatedReason) {
            if (!is_string($language) || strlen($language) !== 2) {
                $messages['reason.' . $language] = 'Language key should be a string of exactly 2 characters';
            }

            if (empty(trim($translatedReason))) {
                $messages['reason.' . $language] = 'Cannot be empty';
            }
        }

        return $messages;
    }
}
