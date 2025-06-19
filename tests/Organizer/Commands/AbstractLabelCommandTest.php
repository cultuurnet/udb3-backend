<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractLabelCommandTest extends TestCase
{
    private string $organizerId;

    private Label $label;

    private AbstractLabelCommand&MockObject $abstractLabelCommand;

    protected function setUp(): void
    {
        $this->organizerId = 'organizerId';

        $this->label = new Label(new LabelName('foo'));

        $this->abstractLabelCommand = $this->getMockForAbstractClass(
            AbstractLabelCommand::class,
            [$this->organizerId, $this->label]
        );
    }

    /**
     * @test
     */
    public function it_stores_an_organizer_id(): void
    {
        $this->assertEquals(
            $this->organizerId,
            $this->abstractLabelCommand->getItemId()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_label(): void
    {
        $this->assertEquals(
            $this->label,
            $this->abstractLabelCommand->getLabel()
        );
    }

    /**
     * @test
     */
    public function it_returns_an_item_id(): void
    {
        $this->assertEquals(
            $this->organizerId,
            $this->abstractLabelCommand->getItemId()
        );
    }

    /**
     * @test
     */
    public function it_returns_a_permission(): void
    {
        $this->assertEquals(
            Permission::organisatiesBewerken(),
            $this->abstractLabelCommand->getPermission()
        );
    }

    /**
     * @test
     */
    public function it_identifies_by_label_name(): void
    {
        $this->assertEquals(
            [
                'foo',
            ],
            $this->abstractLabelCommand->getLabelNames()
        );
    }
}
