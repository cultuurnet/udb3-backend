<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\Trims;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode instead.
 */
final class PostalCode
{
    use Trims;
    use IsString;

    public function __construct(string $value)
    {
        $value = $this->trim($value);
        $this->value = $value;
    }
}
