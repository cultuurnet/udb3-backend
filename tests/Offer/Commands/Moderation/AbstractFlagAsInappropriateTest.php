<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Moderation;

class AbstractFlagAsInappropriateTest extends AbstractModerationCommandTestBase
{
    public function getModerationCommandClass(): string
    {
        return AbstractFlagAsInappropriate::class;
    }
}
