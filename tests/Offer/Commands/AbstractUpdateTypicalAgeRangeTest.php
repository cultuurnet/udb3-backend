<?php

namespace CultuurNet\UDB3\Offer\Commands;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractUpdateTypicalAgeRangeTest extends TestCase
{
    /**
     * @var AbstractUpdateTypicalAgeRange|MockObject
     */
    protected $updateTypicalAgeRange;

    /**
     * @var string
     */
    protected $itemId;

    /**
     * @var string
     */
    protected $typicalAgeRange;

    public function setUp()
    {
        $this->itemId = 'Foo';
        $this->typicalAgeRange = '3-12';

        $this->updateTypicalAgeRange = $this->getMockForAbstractClass(
            AbstractUpdateTypicalAgeRange::class,
            array($this->itemId, $this->typicalAgeRange)
        );
    }

    /**
     * @test
     */
    public function it_can_return_its_properties()
    {
        $typicalAgeRange = $this->updateTypicalAgeRange->getTypicalAgeRange();
        $expectedTypicalAgeRange = '3-12';

        $this->assertEquals($expectedTypicalAgeRange, $typicalAgeRange);

        $itemId = $this->updateTypicalAgeRange->getItemId();
        $expectedItemId = 'Foo';

        $this->assertEquals($expectedItemId, $itemId);
    }
}
