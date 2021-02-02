<?php

namespace CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label;

use PHPUnit\Framework\TestCase;

class LabelTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_a_name_and_a_visibility()
    {
        $name = new LabelName('foo');
        $label = new Label($name, true);

        $this->assertEquals($name, $label->getName());
        $this->assertTrue($label->isVisible());
    }
}
