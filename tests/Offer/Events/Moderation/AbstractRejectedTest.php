<?php

namespace CultuurNet\UDB3\Offer\Events\Moderation;

use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractReject;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class AbstractRejectedTest extends TestCase
{
    /**
     * @var string
     */
    private $itemId;

    /**
     * @var StringLiteral
     */
    private $reason;

    /**
     * @var AbstractRejected
     */
    private $abstractRejected;

    protected function setUp()
    {
        $this->itemId = 'e1d026e2-d158-40e9-b82a-dfcd62de2a77';
        $this->reason = new StringLiteral('Het aanbod is hetzelfde als...');

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
    public function it_stores_an_item_id()
    {
        $this->assertEquals(
            $this->itemId,
            $this->abstractRejected->getItemId()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_reason()
    {
        $this->assertEquals(
            $this->reason,
            $this->abstractRejected->getReason()
        );
    }

    /**
     * @test
     */
    public function it_can_serialize()
    {
        $expectedArray = [
            'item_id' => $this->itemId,
            'reason' => $this->reason,
        ];

        $this->assertEquals($expectedArray, $this->abstractRejected->serialize());
    }
}
