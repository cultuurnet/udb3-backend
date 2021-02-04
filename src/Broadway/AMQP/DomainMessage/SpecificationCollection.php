<?php

namespace CultuurNet\BroadwayAMQP\DomainMessage;

use TwoDotsTwice\Collection\AbstractCollection;

class SpecificationCollection extends AbstractCollection
{
    protected function getValidObjectType()
    {
        return SpecificationInterface::class;
    }
}
