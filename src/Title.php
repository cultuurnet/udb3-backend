<?php

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Model\ValueObject\Text\Title as Udb3ModelTitle;

/**
 * @todo Replace by CultuurNet\UDB3\Model\ValueObject\Text\Title.
 */
class Title extends TrimmedString implements \JsonSerializable
{
    public function __construct($value)
    {
        parent::__construct($value);

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
     * @param Udb3ModelTitle $title
     * @return Title
     */
    public static function fromUdb3ModelTitle(Udb3ModelTitle $title)
    {
        $string = $title->toString();
        return new self($string);
    }
}
