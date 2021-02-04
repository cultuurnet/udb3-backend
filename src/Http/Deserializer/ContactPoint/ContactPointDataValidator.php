<?php

namespace CultuurNet\UDB3\Http\Deserializer\ContactPoint;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;

class ContactPointDataValidator implements DataValidatorInterface
{
    /**
     * @param array $data
     * @throws DataValidationException
     */
    public function validate(array $data)
    {
        $messages = [];

        foreach ($data as $index => $contactPointEntry) {
            if (!isset($contactPointEntry['type'])) {
                $messages[$index . '.type'] = 'Required but could not be found.';
            } elseif (!in_array($contactPointEntry['type'], ['url', 'phone', 'email'])) {
                $messages[$index . '.type'] = 'Invalid type. Allowed types are: url, phone, email.';
            }

            if (empty($contactPointEntry['value'])) {
                $messages[$index . '.value'] = 'Required but could not be found.';
            }
        }

        if (!empty($messages)) {
            $e = new DataValidationException();
            $e->setValidationMessages($messages);
            throw $e;
        }
    }
}
