<?php

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Model\ValueObject\Text\Description as Udb3ModelDescription;
use ValueObjects\StringLiteral\StringLiteral;

class Description extends StringLiteral
{
    /**
     * @param Udb3ModelDescription $udb3ModelDescription
     * @return Description
     */
    public static function fromUdb3ModelDescription(Udb3ModelDescription $udb3ModelDescription)
    {
        return new Description($udb3ModelDescription->toString());
    }
}
