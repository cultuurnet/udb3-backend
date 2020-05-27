<?php

namespace CultuurNet\UDB3\Offer\Commands\Moderation;

class AbstractFlagAsDuplicateTest extends AbstractModerationCommandTestBase
{
    /**
     * @inheritdoc
     */
    public function getModerationCommandClass()
    {
        return AbstractFlagAsDuplicate::class;
    }
}
