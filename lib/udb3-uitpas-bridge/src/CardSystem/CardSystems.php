<?php

namespace CultuurNet\UDB3\UiTPAS\CardSystem;

use TwoDotsTwice\Collection\AbstractCollection;

class CardSystems extends AbstractCollection
{
    protected function getValidObjectType()
    {
        return CardSystem::class;
    }
}
