<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;
use InvalidArgumentException;

class UpdateSubEventsStatusValidator implements DataValidatorInterface
{
    public function validate(array $data): void
    {
        $messages = [];
        foreach ($data as $index => $eventStatus) {
            $eventStatusMessages = $this->validateEventStatus($eventStatus, $index);
            if (!empty($eventStatusMessages)) {
                $messages = array_merge($messages, $eventStatusMessages);
            }
        }

        if (!empty($messages)) {
            $exception = new DataValidationException();
            $exception->setValidationMessages($messages);
            throw $exception;
        }
    }

    private function validateEventStatus(array $data, int $index): array
    {
        $messages = [];
        $messages = array_merge($messages, $this->validateId($data));

        if (!isset($data['status'])) {
            $messages['status'] = 'Required but could not be found';
        } elseif (!is_array($data['status'])) {
            $messages['status'] = 'Should be an object with type and optionally reason properties';
        } else {
            $messages = array_merge($messages, $this->validateType($data));
            $messages = array_merge($messages, $this->validateReasons($data));
        }

        $messagesWithIndexBeforeKey = [];
        foreach ($messages as $key => $message) {
            $messagesWithIndexBeforeKey["[$index].$key"] = $message;
        }

        return $messagesWithIndexBeforeKey;
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

        if (!is_array($data['status']['reason'])) {
            return [
                'status.reason' => 'Should be an object with language codes as properties and string values',
            ];
        }

        $messages = [];
        foreach ($data['status']['reason'] as $language => $translatedReason) {
            if (!is_string($language) || strlen($language) !== 2) {
                $messages['status.reason.' . $language] = 'Language key should be a string of exactly 2 characters';
            }

            if (empty(trim($translatedReason))) {
                $messages['status.reason.' . $language] = 'Cannot be empty';
            }
        }

        return $messages;
    }
}
