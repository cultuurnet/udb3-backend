<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ValueObjects;

use PHPUnit\Framework\TestCase;

class PrivacyTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_a_public_option(): void
    {
        $privacy = Privacy::public();

        $this->assertEquals($privacy->toString(), 'public');
    }

    /**
     * @test
     */
    public function it_has_a_private_option(): void
    {
        $privacy = Privacy::private();

        $this->assertEquals($privacy->toString(), 'private');
    }

    /**
     * @test
     */
    public function it_has_only_a_private_and_public_option(): void
    {
        $options = Privacy::getAllowedValues();

        $this->assertEquals(
            [
                'public',
                'private',
            ],
            $options
        );
    }
}
