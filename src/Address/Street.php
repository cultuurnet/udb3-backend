<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address;

use CultuurNet\UDB3\Model\ValueObject\Geography\Street as Udb3ModelStreet;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\Trims;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Geography\Street instead.
 */
final class Street
{
    use Trims;

    private string $value;

    public function __construct(string $value)
    {
        $value = $this->trim($value);
        $this->value = $value;
    }

    public function toNative(): string
    {
        return $this->value;
    }

    public function sameValueAs(self $title): bool
    {
        return $this->toNative() === $title->toNative();
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public static function fromUdb3ModelStreet(Udb3ModelStreet $title): self
    {
        return new self($title->toString());
    }
}
