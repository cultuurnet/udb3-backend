<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour\Exception;

use InvalidArgumentException;

class StringIsInvalid extends InvalidArgumentException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function becauseMaxLengthIsExceeded(string $field, int $maxLength): self
    {
        return new self(sprintf('Given %s should not be longer than %d characters.', $field, $maxLength));
    }
}
