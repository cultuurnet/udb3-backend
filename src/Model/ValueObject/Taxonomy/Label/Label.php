<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label;

class Label
{
    private LabelName $name;

    private bool $visible;

    public function __construct(LabelName $name, bool $visible = true)
    {
        $this->name = $name;
        $this->visible = $visible;
    }

    public function getName(): LabelName
    {
        return $this->name;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }
}
