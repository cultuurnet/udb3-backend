<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address\StreetSuggester;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class CachedStreetSuggesterTest extends TestCase
{
    /**
     * @var StreetSuggester&MockObject
     */
    private $fallbackStreetSuggester;

    private CachedStreetSuggester $cachedStreetSuggester;

    /**
     * @var string[]
     */
    private array $cachedStreets;

    protected function setUp(): void
    {
        $this->fallbackStreetSuggester = $this->createMock(StreetSuggester::class);
        $cache = new ArrayAdapter();

        $this->cachedStreets = [
            'Koningin Maria Hendrikaplein',
            'Marialand',
            'Maria-Theresiastraat',
            'Maria Van Boergondiëstraat',
        ];

        $cache->get(
            $this->createCacheKey('9000', 'Gent', 'Maria', 5),
            fn () => $this->cachedStreets
        );

        $this->cachedStreetSuggester = new CachedStreetSuggester(
            $this->fallbackStreetSuggester,
            $cache
        );
    }

    /**
     * @test
     */
    public function it_can_get_streets_from_cache(): void
    {
        $this->fallbackStreetSuggester->expects($this->never())
            ->method('suggest');

        $this->assertEquals(
            $this->cachedStreets,
            $this->cachedStreetSuggester->suggest('9000', 'Gent', 'Maria', 5)
        );
    }

    /**
     * @test
     */
    public function it_can_get_uncached_streets_from_the_decoratee(): void
    {
        $uncachedStreets = [
            'Koningin Elisabethlaan',
            'Koningin Astridlaan',
            'Koningin Fabiolalaan',
            'Koningin Maria Hendrikaplein',
        ];
        $this->fallbackStreetSuggester->expects($this->once())
            ->method('suggest')
            ->with('9000', 'Gent', 'Koningin', 5)
            ->willReturn($uncachedStreets);

        $this->assertEquals(
            $uncachedStreets,
            $this->cachedStreetSuggester->suggest('9000', 'Gent', 'Koningin', 5)
        );
    }

    private function createCacheKey(string $postalCode, string $locality, string $streetQuery, int $limit): string
    {
        return preg_replace('/[{}()\/\\\\@:]/', '_', $postalCode . '_' . $locality . '_' . $streetQuery . '_' . (string) $limit);
    }
}
