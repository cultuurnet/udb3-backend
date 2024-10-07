<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\Trims;
use CultuurNet\UDB3\Model\ValueObject\Text\Description as Udb3ModelDescription;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Text\Description instead where possible.
 */
final class Description
{
    use Trims;
    use IsString;

    public function __construct(string $value)
    {
        $value = $this->trim($value);
        $this->value = $value;
    }

    public static function fromUdb3ModelDescription(Udb3ModelDescription $udb3ModelDescription): self
    {
        return new self($udb3ModelDescription->toString());
    }

    public function toUdb3ModelDescription(): Udb3ModelDescription
    {
        return new Udb3ModelDescription($this->toString());
    }
}
