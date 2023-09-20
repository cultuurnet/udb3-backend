<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsNotEmpty;
use CultuurNet\UDB3\Model\ValueObject\Text\Title as Udb3ModelTitle;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Text\Title instead where possible.
 */
final class Title implements \JsonSerializable
{
    use IsNotEmpty;
    private string $value;

    public function __construct(string $value)
    {
        $this->guardNotEmpty($value);
        $this->value = $value;
    }

    public function toNative(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public static function fromUdb3ModelTitle(Udb3ModelTitle $title): self
    {
        return new self($title->toString());
    }
}
