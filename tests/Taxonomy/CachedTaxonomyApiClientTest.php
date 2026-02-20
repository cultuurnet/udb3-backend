<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Taxonomy;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\CacheInterface;

final class CachedTaxonomyApiClientTest extends TestCase
{
    private TaxonomyApiClient&MockObject $baseTaxonomyApiClient;
    private CacheInterface $cache;
    private CachedTaxonomyApiClient $cachedClient;

    protected function setUp(): void
    {
        $this->baseTaxonomyApiClient = $this->createMock(TaxonomyApiClient::class);
        $this->cache = new ArrayAdapter();
        $this->cachedClient = new CachedTaxonomyApiClient($this->baseTaxonomyApiClient, $this->cache);
    }

    /**
     * @test
     */
    public function it_caches_place_types(): void
    {
        $placeTypes = [
            '0.14.0.0.0' => new Category(
                new CategoryID('0.14.0.0.0'),
                new CategoryLabel('Monument'),
                CategoryDomain::eventType()
            ),
        ];

        $this->baseTaxonomyApiClient
            ->expects($this->once())
            ->method('getPlaceTypes')
            ->willReturn($placeTypes);

        $result1 = $this->cachedClient->getPlaceTypes();
        $this->assertEquals($placeTypes, $result1);

        $result2 = $this->cachedClient->getPlaceTypes();
        $this->assertEquals($placeTypes, $result2);
    }

    /**
     * @test
     */
    public function it_caches_place_facilities(): void
    {
        $placeFacilities = [
            '3.27.0.0.0' => new Category(
                new CategoryID('3.27.0.0.0'),
                new CategoryLabel('Rolstoeltoegankelijk'),
                CategoryDomain::facility()
            ),
        ];

        $this->baseTaxonomyApiClient
            ->expects($this->once())
            ->method('getPlaceFacilities')
            ->willReturn($placeFacilities);

        $result1 = $this->cachedClient->getPlaceFacilities();
        $this->assertEquals($placeFacilities, $result1);

        $result2 = $this->cachedClient->getPlaceFacilities();
        $this->assertEquals($placeFacilities, $result2);
    }

    /**
     * @test
     */
    public function it_caches_event_types(): void
    {
        $eventTypes = [
            '0.50.4.0.0' => new Category(
                new CategoryID('0.50.4.0.0'),
                new CategoryLabel('Concert'),
                CategoryDomain::eventType()
            ),
        ];

        $this->baseTaxonomyApiClient
            ->expects($this->once())
            ->method('getEventTypes')
            ->willReturn($eventTypes);

        $result1 = $this->cachedClient->getEventTypes();
        $this->assertEquals($eventTypes, $result1);

        $result2 = $this->cachedClient->getEventTypes();
        $this->assertEquals($eventTypes, $result2);
    }

    /**
     * @test
     */
    public function it_caches_event_themes(): void
    {
        $eventThemes = [
            '1.8.3.5.0' => new Category(
                new CategoryID('1.8.3.5.0'),
                new CategoryLabel('Amusementsmuziek'),
                CategoryDomain::theme()
            ),
        ];

        $this->baseTaxonomyApiClient
            ->expects($this->once())
            ->method('getEventThemes')
            ->willReturn($eventThemes);

        $result1 = $this->cachedClient->getEventThemes();
        $this->assertEquals($eventThemes, $result1);

        $result2 = $this->cachedClient->getEventThemes();
        $this->assertEquals($eventThemes, $result2);
    }

    /**
     * @test
     */
    public function it_caches_event_facilities(): void
    {
        $eventFacilities = [
            '3.13.1.0.0' => new Category(
                new CategoryID('3.13.1.0.0'),
                new CategoryLabel('Voorzieningen voor assistentiehonden'),
                CategoryDomain::facility()
            ),
        ];

        $this->baseTaxonomyApiClient
            ->expects($this->once())
            ->method('getEventFacilities')
            ->willReturn($eventFacilities);

        $result1 = $this->cachedClient->getEventFacilities();
        $this->assertEquals($eventFacilities, $result1);

        $result2 = $this->cachedClient->getEventFacilities();
        $this->assertEquals($eventFacilities, $result2);
    }

    /**
     * @test
     */
    public function it_caches_native_terms(): void
    {
        $nativeTerms = [
            ['id' => '0.50.4.0.0', 'name' => 'Concert'],
            ['id' => '1.8.3.5.0', 'name' => 'Amusementsmuziek'],
        ];

        $this->baseTaxonomyApiClient
            ->expects($this->once())
            ->method('getMapping')
            ->willReturn($nativeTerms);

        $result1 = $this->cachedClient->getNativeTerms();
        $this->assertEquals($nativeTerms, $result1);

        $result2 = $this->cachedClient->getNativeTerms();
        $this->assertEquals($nativeTerms, $result2);
    }

    /**
     * @test
     */
    public function it_uses_separate_cache_keys_for_different_methods(): void
    {
        $placeTypes = [
            '0.14.0.0.0' => new Category(
                new CategoryID('0.14.0.0.0'),
                new CategoryLabel('Monument'),
                CategoryDomain::eventType()
            ),
        ];

        $eventTypes = [
            '0.50.4.0.0' => new Category(
                new CategoryID('0.50.4.0.0'),
                new CategoryLabel('Concert'),
                CategoryDomain::eventType()
            ),
        ];

        $this->baseTaxonomyApiClient
            ->expects($this->once())
            ->method('getPlaceTypes')
            ->willReturn($placeTypes);

        $this->baseTaxonomyApiClient
            ->expects($this->once())
            ->method('getEventTypes')
            ->willReturn($eventTypes);

        // Both methods should be called once and cached separately
        $placeTypesResult = $this->cachedClient->getPlaceTypes();
        $eventTypesResult = $this->cachedClient->getEventTypes();

        $this->assertEquals($placeTypes, $placeTypesResult);
        $this->assertEquals($eventTypes, $eventTypesResult);

        // Verify they're cached independently
        $placeTypesResult2 = $this->cachedClient->getPlaceTypes();
        $eventTypesResult2 = $this->cachedClient->getEventTypes();

        $this->assertEquals($placeTypes, $placeTypesResult2);
        $this->assertEquals($eventTypes, $eventTypesResult2);
    }

    /**
     * @test
     */
    public function it_returns_empty_array_when_base_client_returns_empty_array(): void
    {
        $this->baseTaxonomyApiClient
            ->expects($this->once())
            ->method('getEventTypes')
            ->willReturn([]);

        $result = $this->cachedClient->getEventTypes();
        $this->assertEquals([], $result);

        // Should still cache the empty array
        $result2 = $this->cachedClient->getEventTypes();
        $this->assertEquals([], $result2);
    }
}
