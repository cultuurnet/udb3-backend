<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\Trims;

class LabelName
{
    use IsString;
    use Trims;

    /**
     * @param string $value
     */
    public function __construct($value)
    {
        $value = $this->trim($value);

        if (false !== strpos($value, ';')) {
            throw new \InvalidArgumentException(
                "Label '$value' should not contain semicolons."
            );
        }

        $length = mb_strlen($value);
        if ($length < 2) {
            throw new \InvalidArgumentException(
                "Label '$value' should not be shorter than 2 chars."
            );
        }

        if ($length > 255) {
            throw new \InvalidArgumentException(
                "Label '$value' should not be longer than 255 chars."
            );
        }

        $this->setValue($value);
    }
}
