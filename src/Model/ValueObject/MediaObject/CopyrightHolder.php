<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsNotEmpty;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\Trims;

final class CopyrightHolder
{
    use IsString;
    use IsNotEmpty;
    use Trims;

    public function __construct(string $value)
    {
        $value = $this->trim($value);

        $this->guardNotEmpty($value);

        $length = mb_strlen($value);
        if ($length < 2) {
            throw new \InvalidArgumentException(
                "CopyrightHolder '$value' should not be shorter than 2 chars."
            );
        }

        if ($length > 250) {
            throw new \InvalidArgumentException(
                "CopyrightHolder '$value' should not be longer than 250 chars."
            );
        }

        $this->setValue($value);
    }
}
