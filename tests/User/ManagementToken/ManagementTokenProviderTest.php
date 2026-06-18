<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User\ManagementToken;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

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

    /**
     * @test
     */
    public function it_expires_the_cache_entry_five_minutes_before_the_token_expires(): void
    {
        $tokenGenerator = $this->createMock(ManagementTokenGenerator::class);
        $tokenGenerator->method('newToken')->willReturn(new ManagementToken('my_token', 3600));

        $item = $this->createMock(ItemInterface::class);
        $item->expects($this->once())->method('expiresAfter')->with(3300);

        $this->assertEquals('my_token', $this->providerWithCacheItem($tokenGenerator, $item)->token());
    }

    /**
     * @test
     */
    public function it_does_not_keep_a_token_that_expires_within_the_safety_margin(): void
    {
        $tokenGenerator = $this->createMock(ManagementTokenGenerator::class);
        $tokenGenerator->method('newToken')->willReturn(new ManagementToken('my_token', 120));

        $item = $this->createMock(ItemInterface::class);
        $item->expects($this->once())->method('expiresAfter')->with(0);

        $this->assertEquals('my_token', $this->providerWithCacheItem($tokenGenerator, $item)->token());
    }

    private function providerWithCacheItem(
        ManagementTokenGenerator $tokenGenerator,
        ItemInterface $item
    ): ManagementTokenProvider {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturnCallback(
            static fn (string $key, callable $callback): string => $callback($item)
        );

        return new ManagementTokenProvider($tokenGenerator, $cache);
    }
}
