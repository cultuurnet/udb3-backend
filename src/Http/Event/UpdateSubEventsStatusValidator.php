<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\Deserializer\DataValidationException;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;
use InvalidArgumentException;

class UpdateSubEventsStatusValidator implements DataValidatorInterface
{
    public function validate(array $data): void
    {
        $messages = [];
        foreach ($data as $index => $eventStatus) {
            $eventStatusMessages = $this->validateEventStatus($eventStatus);
            if (!empty($eventStatusMessages)) {
                $messages[$index] = $eventStatusMessages;
            }
        }

        if (!empty($messages)) {
            $exception = new DataValidationException();
            $exception->setValidationMessages($messages);
            throw $exception;
        }
    }

    private function validateEventStatus(array $data): array
    {
        $messages = [];
        $messages = array_merge($messages, $this->validateId($data));
        $messages = array_merge($messages, $this->validateType($data));
        $messages = array_merge($messages, $this->validateReasons($data));

        return $messages;
    }

    private function validateId(array $data): array
    {
        if (!isset($data['id'])) {
            return [
                'id' => 'Required but could not be found',
            ];
        }

        if (!is_int($data['id'])) {
            return [
                'id' => 'Should be an integer',
            ];
        }

        return [];
    }

    private function validateType(array $data): array
    {
        if (!isset($data['status']['type'])) {
            return [
                'status.type' => 'Required but could not be found',
            ];
        }

        try {
            StatusType::fromNative($data['status']['type']);
        } catch (InvalidArgumentException $e) {
            return [
                'status.type' => 'Invalid status provided',
            ];
        }

        return [];
    }

    private function validateReasons(array $data): array
    {
        if (!isset($data['status']['reason'])) {
            return [];
        }

        $messages = [];
        foreach ($data['status']['reason'] as $language => $translatedReason) {
            if (empty(trim($translatedReason))) {
                $messages['status.reason.' . $language] = 'Cannot be empty';
            }
        }

        return $messages;
    }
}
