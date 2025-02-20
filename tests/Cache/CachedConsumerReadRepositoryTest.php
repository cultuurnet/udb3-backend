<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cache;

use CultureFeed_Consumer;
use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerReadRepository;
use CultuurNet\UDB3\ApiGuard\CultureFeed\CultureFeedConsumerAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class CachedConsumerReadRepositoryTest extends TestCase
{
    /**
     * @var ConsumerReadRepository&MockObject
     */
    private $fallbackConsumerReadRepository;

    private ApiKey $cachedApiKey;

    private CachedConsumerReadRepository $cachedConsumerReadRepository;

    private CultureFeed_Consumer $cachedConsumer;

    protected function setUp(): void
    {
        $this->fallbackConsumerReadRepository = $this->createMock(ConsumerReadRepository::class);
        $cache = new ArrayAdapter();
        $this->cachedApiKey = new ApiKey('b26e5a7b-5e01-46c1-8da8-f45edc51d01a');

        $this->cachedConsumer = new CultureFeed_Consumer();
        $this->cachedConsumer->apiKeySapi3 = $this->cachedApiKey->toString();
        $this->cachedConsumer->searchPrefixSapi3 = 'regions:nis-44021';
        $this->cachedConsumer->group = [1, 2, 3];
        $this->cachedConsumer->name = 'Foobar';
        $this->cachedConsumer->status = 'ACTIVE';

        $cache->get(
            $this->cachedApiKey->toString(),
            function () {
                return [
                    'api_key' => $this->cachedConsumer->apiKeySapi3,
                    'default_query' => $this->cachedConsumer->searchPrefixSapi3,
                    'permission_group_ids' => $this->cachedConsumer->group,
                    'name' => $this->cachedConsumer->name,
                    'blocked' => false,
                    'removed' => false,
                    'status' => 'ACTIVE',
                ];
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
            new CultureFeedConsumerAdapter($this->cachedConsumer),
            $this->cachedConsumerReadRepository->getConsumer($this->cachedApiKey)
        );
    }

    /**
     * @test
     */
    public function it_can_get_an_uncached_consumer_from_the_decoretee(): void
    {
        $uncachedApiKey = new ApiKey('c90fbc92-a572-4c39-a002-53f02f58844c');
        $uncachedConsumer = new CultureFeed_Consumer();
        $uncachedConsumer->apiKeySapi3 = $uncachedApiKey->toString();
        $uncachedConsumer->searchPrefixSapi3 = 'regions:nis-44021';
        $uncachedConsumer->group = [4, 5, 6];
        $uncachedConsumer->name = 'Bar Foo';
        $uncachedConsumer->status = 'ACTIVE';

        $this->fallbackConsumerReadRepository->expects($this->once())
            ->method('getConsumer')
            ->willReturn(new CultureFeedConsumerAdapter($uncachedConsumer));

        $this->assertEquals(
            new CultureFeedConsumerAdapter($uncachedConsumer),
            $this->cachedConsumerReadRepository->getConsumer($uncachedApiKey)
        );
    }
}
