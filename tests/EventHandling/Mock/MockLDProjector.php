<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventHandling\Mock;

use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;

class MockLDProjector
{
    use DelegateEventHandlingToSpecificMethodTrait;


    public function applyMockLabelAdded(MockLabelAdded $labelAdded): void
    {
    }


    public function applyMockLabelUpdated(MockLabelUpdatedWrongType $labelUpdated): void
    {
    }

    public function applyMockLabelRemoved(): void
    {
    }


    public function applyMockTitleTranslated(AbstractMockTitleTranslated $translated): void
    {
    }
}
