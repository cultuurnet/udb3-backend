<?php

namespace CultuurNet\UDB3\Curators;

use CultuurNet\UDB3\Label;
use InvalidArgumentException;

class LabelFactory
{
    /**
     * @var array
     */
    private $labelMapping;

    public function __construct(array $labelMapping)
    {
        $this->labelMapping = $labelMapping;
    }

    public function forPublisher(Publisher $publisher): Label
    {
        if (!array_key_exists($publisher->getName(), $this->labelMapping)) {
            throw new InvalidArgumentException('No label defined for publisher ' . $publisher->getName());
        }

        return new Label(
            $this->labelMapping[$publisher->getName()],
            false
        );
    }
}
