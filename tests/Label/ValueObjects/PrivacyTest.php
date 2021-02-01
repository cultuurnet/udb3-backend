<?php

namespace CultuurNet\UDB3\Label\ValueObjects;

use PHPUnit\Framework\TestCase;

class PrivacyTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_a_public_option()
    {
        $privacy = Privacy::PRIVACY_PUBLIC();

        $this->assertEquals($privacy, Privacy::PRIVACY_PUBLIC);
    }

    /**
     * @test
     */
    public function it_has_a_private_option()
    {
        $privacy = Privacy::PRIVACY_PRIVATE();

        $this->assertEquals($privacy, Privacy::PRIVACY_PRIVATE);
    }

    /**
     * @test
     */
    public function it_has_only_a_private_and_public_option()
    {
        $options = Privacy::getConstants();

        $this->assertEquals(
            [
                Privacy::PRIVACY_PUBLIC()->getName() => Privacy::PRIVACY_PUBLIC,
                Privacy::PRIVACY_PRIVATE()->getName() => Privacy::PRIVACY_PRIVATE,
            ],
            $options
        );
    }
}
