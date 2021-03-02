<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation;

use Respect\Validation\Exceptions\GroupedValidationException;

class GroupedValidationExceptionWithoutMainMessage extends GroupedValidationException
{
    /**
     * @inheritdoc
     */
    public function getMessages()
    {
        $messages = [];
        foreach ($this as $exception) {
            $messages[] = $exception->getMessage();
        }
        return $messages;
    }
}
