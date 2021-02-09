<?php

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\TestCase;

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
            new Title('The Title')
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
            new Title('The Title')
        );

        $this->assertEquals($expectedUpdateTitle, $this->updateTitle);
    }
}
