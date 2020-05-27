<?php

namespace CultuurNet\UDB3\Place\Commands;

use CultuurNet\UDB3\Label;
use PHPUnit\Framework\TestCase;

class AddLabelTest extends TestCase
{
    /**
     * @var AddLabel
     */
    private $addLabel;

    protected function setUp()
    {
        $this->addLabel = new AddLabel('itemId', new Label('labelName'));
    }

    /**
     * @test
     */
    public function it_can_serialize()
    {
        $addLabel = unserialize(serialize($this->addLabel));
        $this->assertEquals($this->addLabel, $addLabel);

        $expectedPermission = $this->addLabel->getPermission();
        $permission = $addLabel->getPermission();
        $this->assertEquals($expectedPermission, $permission);
    }
}
