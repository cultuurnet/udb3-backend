<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events\Moderation;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractRejectedTest extends TestCase
{
    private string $itemId;

    private string $reason;

    private AbstractRejected&MockObject $abstractRejected;

    protected function setUp(): void
    {
        $this->itemId = 'e1d026e2-d158-40e9-b82a-dfcd62de2a77';
        $this->reason = 'Het aanbod is hetzelfde als...';

        $this->abstractRejected = $this->getMockForAbstractClass(
            AbstractRejected::class,
            [
                $this->itemId,
                $this->reason,
            ]
        );
    }

    /**
     * @test
     */
    public function it_stores_an_item_id(): void
    {
        $this->assertEquals(
            $this->itemId,
            $this->abstractRejected->getItemId()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_reason(): void
    {
        $this->assertEquals(
            $this->reason,
            $this->abstractRejected->getReason()
        );
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $expectedArray = [
            'item_id' => $this->itemId,
            'reason' => $this->reason,
        ];

        $this->assertEquals($expectedArray, $this->abstractRejected->serialize());
    }
}
