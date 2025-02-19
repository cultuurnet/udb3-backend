<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cache;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKeyAuthenticationException;
use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKeyAuthenticator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class CachedApiKeyAuthenticatorTest extends TestCase
{
    /**
     * @var ApiKeyAuthenticator&MockObject
     */
    private $fallbackApiKeyAuthenticator;

    private ApiKey $cachedApiKey;

    private CachedApiKeyAuthenticator $cachedApiKeyAuthenticator;

    protected function setUp(): void
    {
        $this->fallbackApiKeyAuthenticator = $this->createMock(ApiKeyAuthenticator::class);
        $cache = new ArrayAdapter();
        $this->cachedApiKey = new ApiKey('b26e5a7b-5e01-46c1-8da8-f45edc51d01a');

        $this->cachedApiKeyAuthenticator = new CachedApiKeyAuthenticator(
            $this->fallbackApiKeyAuthenticator,
            $cache
        );

        $cache->get(
            $this->cachedApiKey->toString(),
            function () {
                return false;
            }
        );
    }

    /**
     * @test
     */
    public function it_can_get_a_cached_api_key(): void
    {
        $this->fallbackApiKeyAuthenticator->expects($this->never())
            ->method('authenticate');

        $this->expectException(ApiKeyAuthenticationException::class);

        $this->cachedApiKeyAuthenticator->authenticate($this->cachedApiKey);
    }

    /**
     * @test
     */
    public function it_can_get_an_uncached_api_key_from_the_decoree(): void
    {
        $uncachedApiKey = new ApiKey('17bd454a-7bf4-4c70-8182-cb7d6b48dfac');
        $this->fallbackApiKeyAuthenticator->expects($this->once())
            ->method('authenticate')
        ->willThrowException(ApiKeyAuthenticationException::forApiKey($uncachedApiKey));

        $this->expectException(ApiKeyAuthenticationException::class);

        $this->cachedApiKeyAuthenticator->authenticate($uncachedApiKey);
    }
}
