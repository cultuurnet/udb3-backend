<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ValueObjects;

use PHPUnit\Framework\TestCase;

class VisibilityTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_a_visible_option(): void
    {
        $visibility = Visibility::visible();

        $this->assertEquals($visibility->toString(), 'visible');
    }

    /**
     * @test
     */
    public function it_has_an_invisible_option(): void
    {
        $visibility = Visibility::invisible();

        $this->assertEquals($visibility->toString(), 'invisible');
    }

    /**
     * @test
     */
    public function it_has_only_a_visible_and_invisible_option(): void
    {
        $options = Visibility::getAllowedValues();

        $this->assertEquals(
            [
                'visible',
                'invisible',
            ],
            $options
        );
    }
}
