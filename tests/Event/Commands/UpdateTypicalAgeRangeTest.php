<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Offer\AgeRange;
use PHPUnit\Framework\TestCase;

class UpdateTypicalAgeRangeTest extends TestCase
{
    protected UpdateTypicalAgeRange $updateTypicalAgeRange;

    public function setUp(): void
    {
        $this->updateTypicalAgeRange = new UpdateTypicalAgeRange('id', AgeRange::fromString('1-14'));
    }

    /**
     * @test
     */
    public function it_is_possible_to_instantiate_the_command_with_parameters(): void
    {
        $expectedTypicalAgeRange = new UpdateTypicalAgeRange('id', AgeRange::fromString('1-14'));

        $this->assertEquals($expectedTypicalAgeRange, $this->updateTypicalAgeRange);
    }
}
