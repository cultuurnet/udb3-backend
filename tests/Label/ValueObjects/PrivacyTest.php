<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ValueObjects;

use PHPUnit\Framework\TestCase;

class PrivacyTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_a_public_option()
    {
        $privacy = Privacy::public();

        $this->assertEquals($privacy, Privacy::public());
    }

    /**
     * @test
     */
    public function it_has_a_private_option()
    {
        $privacy = Privacy::private();

        $this->assertEquals($privacy, Privacy::private());
    }

    /**
     * @test
     */
    public function it_has_only_a_private_and_public_option()
    {
        $options = Privacy::getAllowedValues();

        $this->assertEquals(
            [
                Privacy::public()->toString(),
                Privacy::private()->toString(),
            ],
            $options
        );
    }
}
