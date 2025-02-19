<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cache;

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

    protected function setUp(): void
    {
        $this->fallbackConsumerReadRepository = $this->createMock(ConsumerReadRepository::class);
        $cache = new ArrayAdapter();
        $this->cachedApiKey = new ApiKey('b26e5a7b-5e01-46c1-8da8-f45edc51d01a');

        $this->cachedConsumerReadRepository = new CachedConsumerReadRepository(
            $this->fallbackConsumerReadRepository,
            $cache
        );

        $cache->get(
            $this->cachedApiKey->toString(),
            function () {
                return [
                    'api_key' => $this->cachedApiKey->toString(),
                    'default_query' => '',
                    'permission_group_ids' => [1, 2, 3],
                    'name' => 'FOOBAR',
                    'blocked' => false,
                    'removed' => false,
                ];
            }
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
            new CultureFeedConsumerAdapter(
                new CultureFeed_Consumer()
            ),
            $this->cachedConsumerReadRepository->getConsumer($this->cachedApiKey)
        );
    }

    /**
     * @test
     */
    public function it_can_get_an_uncached_consumer_from_the_decoretee(): void
    {

    }
}
