<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour\Exception;

use InvalidArgumentException;

class MaxLengthExceeded extends InvalidArgumentException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function maxLengthExceeded(int $maxLength): self
    {
        return new self(sprintf('Given string should not be longer than %d characters.', $maxLength));
    }

    public function getPath(): string
    {
        return '/0/title';
    }
}
