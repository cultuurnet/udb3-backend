<?php

namespace CultuurNet\UDB3\EventHandling\Mock;

use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;

class MockLDProjector
{
    use DelegateEventHandlingToSpecificMethodTrait;

    /**
     * @param MockLabelAdded $labelAdded
     */
    public function applyMockLabelAdded(MockLabelAdded $labelAdded)
    {
    }

    /**
     * @param MockLabelUpdatedWrongType $labelUpdated
     */
    public function applyMockLabelUpdated(MockLabelUpdatedWrongType $labelUpdated)
    {
    }

    public function applyMockLabelRemoved()
    {
    }

    /**
     * @param AbstractMockTitleTranslated $translated
     */
    public function applyMockTitleTranslated(AbstractMockTitleTranslated $translated)
    {
    }
}
