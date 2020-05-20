<?php

namespace CultuurNet\UDB3\Offer\Commands\Moderation;

class AbstractFlagAsInappropriateTest extends AbstractModerationCommandTestBase
{
    /**
     * @inheritdoc
     */
    public function getModerationCommandClass()
    {
        return AbstractFlagAsInappropriate::class;
    }
}
