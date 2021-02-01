<?php

namespace CultuurNet\UDB3\ValueObject;

use PHPUnit\Framework\TestCase;

class SapiVersionTest extends TestCase
{
    /**
     * @var SapiVersion
     */
    private $sapiVersion;

    protected function setUp(): void
    {
        $this->sapiVersion = SapiVersion::V2();
    }

    /**
     * @test
     */
    public function it_can_be_converted_to_native(): void
    {
        $this->assertEquals(
            'v2',
            $this->sapiVersion->toNative()
        );
    }

    /**
     * @test
     */
    public function it_can_be_compared(): void
    {
        $sameSapiVersion = SapiVersion::V2();
        $otherSapiVersion = SapiVersion::V3();

        $this->assertTrue(
            $this->sapiVersion->sameValueAs($sameSapiVersion)
        );

        $this->assertFalse(
            $this->sapiVersion->sameValueAs($otherSapiVersion)
        );
    }

    /**
     * @test
     */
    public function it_throws_for_invalid_values(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        SapiVersion::fromNative('invalid');
    }
}
