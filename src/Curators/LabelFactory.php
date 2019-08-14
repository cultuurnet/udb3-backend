<?php

namespace CultuurNet\UDB3\Curators;

use CultuurNet\UDB3\Label;
use InvalidArgumentException;

class LabelFactory
{
    private const BRUZZ = 'BRUZZ-redactioneel';

    public function forPublisher(Publisher $publisher): Label
    {
        switch ($publisher) {
            case Publisher::bruzz():
                return new Label(self::BRUZZ, false);
                break;
            default:
                throw new InvalidArgumentException('No label defined for publisher ' . $publisher->getName());
        }
    }
}
