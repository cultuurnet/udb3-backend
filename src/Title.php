<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Model\ValueObject\Text\Title as Udb3ModelTitle;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Text\Title instead where possible.
 */
class Title extends StringLiteral implements \JsonSerializable
{
    public function __construct(string $value)
    {
        parent::__construct(trim($value));

        if ($this->isEmpty()) {
            throw new \InvalidArgumentException('Title can not be empty.');
        }
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return (string)$this;
    }

    /**
     * @return Title
     */
    public static function fromUdb3ModelTitle(Udb3ModelTitle $title)
    {
        $string = $title->toString();
        return new self($string);
    }
}
