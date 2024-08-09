<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Offer\AgeRange;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractUpdateTypicalAgeRangeTest extends TestCase
{
    /**
     * @var AbstractUpdateTypicalAgeRange&MockObject
     */
    protected $updateTypicalAgeRange;

    protected string $itemId;

    protected AgeRange $typicalAgeRange;

    public function setUp(): void
    {
        $this->itemId = 'Foo';
        $this->typicalAgeRange = AgeRange::fromString('3-12');

        $this->updateTypicalAgeRange = $this->getMockForAbstractClass(
            AbstractUpdateTypicalAgeRange::class,
            [$this->itemId, $this->typicalAgeRange]
        );
    }

    /**
     * @test
     */
    public function it_can_return_its_properties(): void
    {
        $typicalAgeRange = $this->updateTypicalAgeRange->getTypicalAgeRange();
        $expectedTypicalAgeRange = AgeRange::fromString('3-12');

        $this->assertEquals($expectedTypicalAgeRange, $typicalAgeRange);

        $itemId = $this->updateTypicalAgeRange->getItemId();
        $expectedItemId = 'Foo';

        $this->assertEquals($expectedItemId, $itemId);
    }
}
