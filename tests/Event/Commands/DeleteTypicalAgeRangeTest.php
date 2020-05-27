<?php

namespace CultuurNet\UDB3\Event\Commands;

use PHPUnit\Framework\TestCase;

class DeleteTypicalAgeRangeTest extends TestCase
{
    /**
     * @var DeleteTypicalAgeRange
     */
    protected $deleteTypicalAgeRange;

    public function setUp()
    {
        $this->deleteTypicalAgeRange = new DeleteTypicalAgeRange('id');
    }

    /**
     * @test
     */
    public function it_is_possible_to_instantiate_the_command_with_parameters()
    {
        $expectedDeleteTypicalAgeRange = new DeleteTypicalAgeRange('id');

        $this->assertEquals($expectedDeleteTypicalAgeRange, $this->deleteTypicalAgeRange);
    }

    /**
     * @test
     */
    public function it_can_return_its_id()
    {
        $expectedId = 'id';
        $this->assertEquals($expectedId, $this->deleteTypicalAgeRange->getItemId());
    }
}
