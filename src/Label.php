<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Label\ValueObjects\LabelName;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label instead where possible.
 */
class Label
{
    /**
     * @var LabelName
     */
    protected $labelName;

    /**
     * @var bool
     */
    protected $visible;

    /**
     * Label constructor.
     * @param string $value
     * @param bool $visible
     */
    public function __construct($value, $visible = true)
    {
        // Try constructing a LabelName object, so the same validation rules hold.
        $this->labelName = new LabelName($value);

        if (!is_bool($visible)) {
            throw new \InvalidArgumentException(sprintf(
                'Value for argument $visible should be a boolean, got a value of type %s.',
                gettype($visible)
            ));
        }

        $this->visible = $visible;
    }

    /**
     * @return bool
     */
    public function equals(Label $label)
    {
        return strcmp(
            mb_strtolower((string) $this, 'UTF-8'),
            mb_strtolower((string) $label, 'UTF-8')
        ) == 0;
    }

    /**
     * @return boolean
     */
    public function isVisible()
    {
        return $this->visible;
    }

    public function getName(): LabelName
    {
        return $this->labelName;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->labelName->toNative();
    }
}
