<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractLabelCommandTest extends TestCase
{
    /**
     * @var AbstractLabelCommand&MockObject
     */
    protected $labelCommand;

    protected string $itemId;

    /**
     * @var Permission[]
     */
    protected array $permissions;

    protected Label $label;

    public function setUp(): void
    {
        $this->itemId = 'Foo';
        $this->label = new Label(new LabelName('LabelTest'));

        $this->labelCommand = $this->getMockForAbstractClass(
            AbstractLabelCommand::class,
            [$this->itemId, $this->label]
        );
    }

    /**
     * @test
     */
    public function it_can_return_its_properties(): void
    {
        $label = $this->labelCommand->getLabel();
        $expectedLabel = new Label(new LabelName('LabelTest'));

        $this->assertEquals($expectedLabel, $label);

        $itemId = $this->labelCommand->getItemId();
        $expectedItemId = 'Foo';

        $this->assertEquals($expectedItemId, $itemId);

        $permission = $this->labelCommand->getPermission();
        $expectedPermission = Permission::aanbodBewerken();

        $this->assertEquals($expectedPermission, $permission);
    }

    /**
     * @test
     */
    public function it_does_use_label_name(): void
    {
        $this->assertEquals(
            [
                'LabelTest',
            ],
            $this->labelCommand->getLabelNames()
        );
    }
}
