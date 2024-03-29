<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Moderation;

class AbstractRejectTest extends AbstractModerationCommandTestBase
{
    public function getModerationCommandClass(): string
    {
        return AbstractReject::class;
    }

    /**
     * @test
     */
    public function it_stores_a_reason(): void
    {
        $reason = 'This event is the same as.';

        /** @var AbstractReject $abstractReject */
        $abstractReject = $this->getMockForAbstractClass(
            AbstractReject::class,
            [
                'e1d026e2-d158-40e9-b82a-dfcd62de2a77',
                $reason,
            ]
        );

        $this->assertEquals($reason, $abstractReject->getReason());
    }
}
