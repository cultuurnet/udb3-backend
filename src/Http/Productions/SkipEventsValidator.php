<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Productions;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class SkipEventsValidator implements DataValidatorInterface
{
    public function validate(array $data): void
    {
        $messages = [];
        $messages = array_merge($messages, $this->validateEvents($data));

        if (!empty($messages)) {
            $exception = new DataValidationException();
            $exception->setValidationMessages($messages);
            throw $exception;
        }
    }

    private function validateEvents(array $data): array
    {
        if (!isset($data['eventIds'])) {
            return [
                'eventIds' => 'Required but could not be found',
            ];
        }

        if (count($data['eventIds']) < 2) {
            return [
                'eventIds' => 'At least two events should be provided',
            ];
        }

        return [];
    }
}
