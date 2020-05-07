<?php

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Language;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class UpdateTitleTest extends TestCase
{
    /**
     * @var UpdateTitle
     */
    protected $updateTitle;

    public function setUp()
    {
        $this->updateTitle = new UpdateTitle(
            'id',
            new Language('en'),
            new StringLiteral('The Title')
        );
    }

    /**
     * @test
     */
    public function it_is_possible_to_instantiate_the_command_with_parameters()
    {
        $expectedUpdateTitle = new UpdateTitle(
            'id',
            new Language('en'),
            new StringLiteral('The Title')
        );

        $this->assertEquals($expectedUpdateTitle, $this->updateTitle);
    }
}
