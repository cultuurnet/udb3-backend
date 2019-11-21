<?php

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class AbstractLabelCommandTest extends TestCase
{
    /**
     * @var string
     */
    private $organizerId;

    /**
     * @var Label
     */
    private $label;

    /**
     * @var AbstractLabelCommand
     */
    private $abstractLabelCommand;

    protected function setUp()
    {
        $this->organizerId = 'organizerId';

        $this->label = new Label('foo');

        $this->abstractLabelCommand = $this->getMockForAbstractClass(
            AbstractLabelCommand::class,
            [$this->organizerId, $this->label]
        );
    }

    /**
     * @test
     */
    public function it_stores_an_organizer_id()
    {
        $this->assertEquals(
            $this->organizerId,
            $this->abstractLabelCommand->getOrganizerId()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_label()
    {
        $this->assertEquals(
            $this->label,
            $this->abstractLabelCommand->getLabel()
        );
    }

    /**
     * @test
     */
    public function it_returns_an_item_id()
    {
        $this->assertEquals(
            $this->organizerId,
            $this->abstractLabelCommand->getItemId()
        );
    }

    /**
     * @test
     */
    public function it_returns_a_permission()
    {
        $this->assertEquals(
            Permission::ORGANISATIES_BEWERKEN(),
            $this->abstractLabelCommand->getPermission()
        );
    }

    /**
     * @test
     */
    public function it_identifies_by_label_name()
    {
        $this->assertEquals(
            [
                new StringLiteral('foo'),
            ],
            $this->abstractLabelCommand->getNames()
        );
    }
}
