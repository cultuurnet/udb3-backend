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

    public function forPublisher(PublisherName $publisher): Label
    {
        foreach (array_keys($this->labelMapping) as $key) {
            if ($publisher->equals(new PublisherName($key))) {
                return new Label($this->labelMapping[$key], false);
            }
        }

        throw new InvalidArgumentException('No label defined for publisher ' . $publisher->toString());
    }
}
