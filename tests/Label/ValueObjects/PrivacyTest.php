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
        $privacy = Privacy::PRIVACY_PUBLIC();

        $this->assertEquals($privacy->toString(), Privacy::PRIVACY_PUBLIC);
    }

    /**
     * @test
     */
    public function it_has_a_private_option()
    {
        $privacy = Privacy::PRIVACY_PRIVATE();

        $this->assertEquals($privacy->toString(), Privacy::PRIVACY_PRIVATE);
    }

    /**
     * @test
     */
    public function it_has_only_a_private_and_public_option()
    {
        $options = Privacy::getAllowedValues();

        $this->assertEquals(
            [
                Privacy::PRIVACY_PUBLIC,
                Privacy::PRIVACY_PRIVATE,
            ],
            $options
        );
    }
}
