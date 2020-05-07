<?php

namespace CultuurNet\UDB3\Event\Commands;

use PHPUnit\Framework\TestCase;

class UpdateTypicalAgeRangeTest extends TestCase
{
    /**
     * @var UpdateTypicalAgeRange
     */
    protected $updateTypicalAgeRange;

    public function setUp()
    {
        $this->updateTypicalAgeRange = new UpdateTypicalAgeRange('id', '1-14');
    }

    /**
     * @test
     */
    public function it_is_possible_to_instantiate_the_command_with_parameters()
    {
        $expectedTypicalAgeRange = new UpdateTypicalAgeRange('id', '1-14');

        $this->assertEquals($expectedTypicalAgeRange, $this->updateTypicalAgeRange);
    }
}
