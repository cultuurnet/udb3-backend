<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventHandling\Mock;

use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;

class MockLDProjector
{
    use DelegateEventHandlingToSpecificMethodTrait;


    public function applyMockLabelAdded(MockLabelAdded $labelAdded)
    {
    }


    public function applyMockLabelUpdated(MockLabelUpdatedWrongType $labelUpdated)
    {
    }

    public function applyMockLabelRemoved()
    {
    }


    public function applyMockTitleTranslated(AbstractMockTitleTranslated $translated)
    {
    }
}
