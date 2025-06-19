<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cache;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\ApiGuard\Consumer\Consumer;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerReadRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class CachedConsumerReadRepositoryTest extends TestCase
{
    private ConsumerReadRepository&MockObject $fallbackConsumerReadRepository;

    private ApiKey $cachedApiKey;

    private CachedConsumerReadRepository $cachedConsumerReadRepository;

    private Consumer $cachedConsumer;

    protected function setUp(): void
    {
        $this->fallbackConsumerReadRepository = $this->createMock(ConsumerReadRepository::class);
        $cache = new ArrayAdapter();
        $this->cachedApiKey = new ApiKey('b26e5a7b-5e01-46c1-8da8-f45edc51d01a');

        $this->cachedConsumer = SerializableConsumer::deserialize([
            'api_key' => $this->cachedApiKey,
            'default_query' => 'regions:nis-44021',
            'permission_group_ids' => [1, 2, 3],
            'name' => 'Foobar',
            'is_blocked' => false,
            'is_removed' => false,
        ]);

        $cache->get(
            $this->cachedApiKey->toString(),
            function () {
                return SerializableConsumer::serialize($this->cachedConsumer);
            }
        );

        $this->cachedConsumerReadRepository = new CachedConsumerReadRepository(
            $this->fallbackConsumerReadRepository,
            $cache
        );
    }

    /**
     * @test
     */
    public function it_can_get_a_cached_consumer(): void
    {
        $this->fallbackConsumerReadRepository->expects($this->never())
            ->method('getConsumer');

        $this->assertEquals(
            $this->cachedConsumer,
            $this->cachedConsumerReadRepository->getConsumer($this->cachedApiKey)
        );
    }

    /**
     * @test
     */
    public function it_can_get_an_uncached_consumer_from_the_decoretee(): void
    {
        $uncachedApiKey = new ApiKey('c90fbc92-a572-4c39-a002-53f02f58844c');

        $uncachedConsumer = SerializableConsumer::deserialize([
            'api_key' => $uncachedApiKey,
            'default_query' => 'regions:nis-44021',
            'permission_group_ids' => [4, 5, 6],
            'name' => 'Bar Foo',
            'is_blocked' => false,
            'is_removed' => false,
        ]);

        $this->fallbackConsumerReadRepository->expects($this->once())
            ->method('getConsumer')
            ->with($uncachedApiKey)
            ->willReturn($uncachedConsumer);

        $this->assertEquals(
            $uncachedConsumer,
            $this->cachedConsumerReadRepository->getConsumer($uncachedApiKey)
        );
    }
}
