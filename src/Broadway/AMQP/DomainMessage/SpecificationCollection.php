<?php

namespace CultuurNet\UDB3\Broadway\AMQP\DomainMessage;

use TwoDotsTwice\Collection\AbstractCollection;

class SpecificationCollection extends AbstractCollection
{
    protected function getValidObjectType()
    {
        return SpecificationInterface::class;
    }
}
