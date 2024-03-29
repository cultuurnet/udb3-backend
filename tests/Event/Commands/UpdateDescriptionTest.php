<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Language;
use PHPUnit\Framework\TestCase;

class UpdateDescriptionTest extends TestCase
{
    /**
     * @var UpdateDescription
     */
    protected $updateDescription;

    public function setUp(): void
    {
        $this->updateDescription = new UpdateDescription(
            'id',
            new Language('fr'),
            new Description('La description')
        );
    }

    /**
     * @test
     */
    public function it_is_possible_to_instantiate_the_command_with_parameters(): void
    {
        $expectedUpdateDescription = new UpdateDescription(
            'id',
            new Language('fr'),
            new Description('La description')
        );

        $this->assertEquals($expectedUpdateDescription, $this->updateDescription);
    }
}
