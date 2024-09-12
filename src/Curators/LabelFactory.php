<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Curators;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use InvalidArgumentException;

class LabelFactory
{
    private array $labelMapping;

    public function __construct(array $labelMapping)
    {
        $this->labelMapping = $labelMapping;
    }

    public function forPublisher(PublisherName $publisher): Label
    {
        foreach (array_keys($this->labelMapping) as $key) {
            if ($publisher->equals(new PublisherName($key))) {
                return new Label(new LabelName($this->labelMapping[$key]), false);
            }
        }

        throw new InvalidArgumentException('No label defined for publisher ' . $publisher->toString());
    }
}
