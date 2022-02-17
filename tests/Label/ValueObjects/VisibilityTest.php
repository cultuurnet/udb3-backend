<?php

declare(strict_types=1);

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

        $this->assertEquals($visibility->toString(), Visibility::VISIBLE);
    }

    /**
     * @test
     */
    public function it_has_an_invisible_option()
    {
        $visibility = Visibility::INVISIBLE();

        $this->assertEquals($visibility->toString(), Visibility::INVISIBLE);
    }

    /**
     * @test
     */
    public function it_has_only_a_visible_and_invisible_option()
    {
        $options = Visibility::getAllowedValues();

        $this->assertEquals(
            [
                Visibility::VISIBLE,
                Visibility::INVISIBLE,
            ],
            $options
        );
    }
}
