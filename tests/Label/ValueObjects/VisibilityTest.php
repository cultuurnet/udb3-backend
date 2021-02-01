<?php

namespace CultuurNet\UDB3\Label\ValueObjects;

use PHPUnit\Framework\TestCase;

class VisibilityTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_a_visible_option()
    {
        $visibility = Visibility::VISIBLE();

        $this->assertEquals($visibility, Visibility::VISIBLE);
    }

    /**
     * @test
     */
    public function it_has_an_invisible_option()
    {
        $visibility = Visibility::INVISIBLE();

        $this->assertEquals($visibility, Visibility::INVISIBLE);
    }

    /**
     * @test
     */
    public function it_has_only_a_visible_and_invisible_option()
    {
        $options = Visibility::getConstants();

        $this->assertEquals(
            [
                Visibility::VISIBLE()->getName() => Visibility::VISIBLE,
                Visibility::INVISIBLE()->getName() => Visibility::INVISIBLE,
            ],
            $options
        );
    }
}
