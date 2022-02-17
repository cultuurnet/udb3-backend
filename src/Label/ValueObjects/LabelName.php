<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ValueObjects;

use CultuurNet\UDB3\StringLiteral;

/**
 * @deprecated
 * Use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName when possible
 */
class LabelName extends StringLiteral
{
    /**
     * @param string $value
     */
    public function __construct($value)
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        if (!is_string($value)) {
            throw new \InvalidArgumentException(
                "Type for argument $value should by string."
            );
        }
        parent::__construct($value);

        if (false !== strpos($value, ';')) {
            throw new \InvalidArgumentException(
                "Value for argument $value should not contain semicolons."
            );
        }

        $length = mb_strlen($value);
        if ($length < 2) {
            throw new \InvalidArgumentException(
                "Value for argument $value should not be shorter than 2 chars."
            );
        }

        if ($length > 255) {
            throw new \InvalidArgumentException(
                "Value for argument $value should not be longer than 255 chars."
            );
        }
    }
}
