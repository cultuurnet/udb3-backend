<?php

namespace CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label;

class Label
{
    /**
     * @var LabelName
     */
    private $name;

    /**
     * @var bool
     */
    private $visible;

    /**
     * @param LabelName $name
     * @param bool $visible
     */
    public function __construct(LabelName $name, $visible = true)
    {
        $this->name = $name;
        $this->visible = $visible;
    }

    /**
     * @return LabelName
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isVisible()
    {
        return $this->visible;
    }
}
