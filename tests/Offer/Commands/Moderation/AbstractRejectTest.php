<?php

namespace CultuurNet\UDB3\Offer\Commands\Moderation;

use ValueObjects\StringLiteral\StringLiteral;

class AbstractRejectTest extends AbstractModerationCommandTestBase
{
    /**
     * @inheritdoc
     */
    public function getModerationCommandClass()
    {
        return AbstractReject::class;
    }

    /**
     * @test
     */
    public function it_stores_a_reason()
    {
        $reason = new StringLiteral('This event is the same as.');

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
