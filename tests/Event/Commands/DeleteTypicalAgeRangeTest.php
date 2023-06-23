<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use PHPUnit\Framework\TestCase;

class DeleteTypicalAgeRangeTest extends TestCase
{
    /**
     * @var DeleteTypicalAgeRange
     */
    protected $deleteTypicalAgeRange;

    public function setUp(): void
    {
        $this->deleteTypicalAgeRange = new DeleteTypicalAgeRange('id');
    }

    /**
     * @test
     */
    public function it_is_possible_to_instantiate_the_command_with_parameters(): void
    {
        $expectedDeleteTypicalAgeRange = new DeleteTypicalAgeRange('id');

        $this->assertEquals($expectedDeleteTypicalAgeRange, $this->deleteTypicalAgeRange);
    }

    /**
     * @test
     */
    public function it_can_return_its_id(): void
    {
        $expectedId = 'id';
        $this->assertEquals($expectedId, $this->deleteTypicalAgeRange->getItemId());
    }
}
