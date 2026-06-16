<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User\ManagementToken;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class ManagementTokenProviderTest extends TestCase
{
    /**
     * @test
     */
    public function it_generates_a_token_on_a_cache_miss(): void
    {
        $tokenGenerator = $this->createMock(ManagementTokenGenerator::class);
        $tokenGenerator->expects($this->once())
            ->method('newToken')
            ->willReturn(new ManagementToken('my_token', 3600));

        $provider = new ManagementTokenProvider($tokenGenerator, new ArrayAdapter());

        $this->assertEquals('my_token', $provider->token());
    }

    /**
     * @test
     */
    public function it_reuses_the_cached_token_without_regenerating(): void
    {
        $tokenGenerator = $this->createMock(ManagementTokenGenerator::class);
        $tokenGenerator->expects($this->once())
            ->method('newToken')
            ->willReturn(new ManagementToken('my_token', 3600));

        $provider = new ManagementTokenProvider($tokenGenerator, new ArrayAdapter());

        $this->assertEquals('my_token', $provider->token());
        $this->assertEquals('my_token', $provider->token());
    }
}
