<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Taxonomy;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class JsonTaxonomyApiClientTest extends TestCase
{
    private ClientInterface&MockObject $httpClient;
    private string $termsEndpoint;
    private JsonTaxonomyApiClient $client;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->termsEndpoint = 'https://taxonomy.example.com/terms';

        $termsData = [
            'terms' => [
                [
                    'id' => '0.50.4.0.0',
                    'domain' => 'eventtype',
                    'scope' => ['events'],
                    'name' => ['nl' => 'Concert'],
                ],
                [
                    'id' => '1.8.3.5.0',
                    'domain' => 'theme',
                    'scope' => ['events'],
                    'name' => ['nl' => 'Amusementsmuziek'],
                ],
                [
                    'id' => '3.13.1.0.0',
                    'domain' => 'facility',
                    'scope' => ['events', 'places'],
                    'name' => ['nl' => 'Voorzieningen voor assistentiehonden'],
                ],
                [
                    'id' => '0.14.0.0.0',
                    'domain' => 'eventtype',
                    'scope' => ['places'],
                    'name' => ['nl' => 'Monument'],
                ],
                [
                    'id' => '3.27.0.0.0',
                    'domain' => 'facility',
                    'scope' => ['places'],
                    'name' => ['nl' => 'Rolstoeltoegankelijk'],
                ],
            ],
        ];

        $responseBody = $this->createMock(StreamInterface::class);
        $responseBody->method('getContents')
            ->willReturn(json_encode($termsData));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')
            ->willReturn($responseBody);

        $this->httpClient->method('sendRequest')
            ->willReturn($response);

        $this->client = new JsonTaxonomyApiClient($this->httpClient, $this->termsEndpoint);
    }

    /**
     * @test
     */
    public function it_fetches_and_stores_terms_on_construction(): void
    {
        $mapping = $this->client->getMapping();
        $this->assertCount(5, $mapping);
        $this->assertIsArray($mapping);
    }

    /**
     * @test
     */
    public function it_returns_all_terms_as_mapping(): void
    {
        $mapping = $this->client->getMapping();

        $this->assertIsArray($mapping);
        $this->assertCount(5, $mapping);
        $this->assertEquals('0.50.4.0.0', $mapping[0]['id']);
        $this->assertEquals('Concert', $mapping[0]['name']['nl']);
    }

    /**
     * @test
     */
    public function it_returns_event_types_filtered_by_domain_and_scope(): void
    {
        $eventTypes = $this->client->getEventTypes();

        $this->assertIsArray($eventTypes);
        $this->assertCount(1, $eventTypes);
        $this->assertArrayHasKey('0.50.4.0.0', $eventTypes);

        $concert = $eventTypes['0.50.4.0.0'];
        $this->assertInstanceOf(Category::class, $concert);
        $this->assertEquals('0.50.4.0.0', $concert->getId()->toString());
        $this->assertEquals('Concert', $concert->getLabel()->toString());
        $this->assertEquals(CategoryDomain::eventType(), $concert->getDomain());
    }

    /**
     * @test
     */
    public function it_returns_event_themes_filtered_by_domain_and_scope(): void
    {
        $eventThemes = $this->client->getEventThemes();

        $this->assertIsArray($eventThemes);
        $this->assertCount(1, $eventThemes);
        $this->assertArrayHasKey('1.8.3.5.0', $eventThemes);

        $theme = $eventThemes['1.8.3.5.0'];
        $this->assertInstanceOf(Category::class, $theme);
        $this->assertEquals('1.8.3.5.0', $theme->getId()->toString());
        $this->assertEquals('Amusementsmuziek', $theme->getLabel()->toString());
        $this->assertEquals(CategoryDomain::theme(), $theme->getDomain());
    }

    /**
     * @test
     */
    public function it_returns_event_facilities_filtered_by_domain_and_scope(): void
    {
        $eventFacilities = $this->client->getEventFacilities();

        $this->assertIsArray($eventFacilities);
        $this->assertCount(1, $eventFacilities);
        $this->assertArrayHasKey('3.13.1.0.0', $eventFacilities);

        $facility = $eventFacilities['3.13.1.0.0'];
        $this->assertInstanceOf(Category::class, $facility);
        $this->assertEquals('3.13.1.0.0', $facility->getId()->toString());
        $this->assertEquals('Voorzieningen voor assistentiehonden', $facility->getLabel()->toString());
        $this->assertEquals(CategoryDomain::facility(), $facility->getDomain());
    }

    /**
     * @test
     */
    public function it_returns_place_types_filtered_by_domain_and_scope(): void
    {
        $placeTypes = $this->client->getPlaceTypes();

        $this->assertIsArray($placeTypes);
        $this->assertCount(1, $placeTypes);
        $this->assertArrayHasKey('0.14.0.0.0', $placeTypes);

        $placeType = $placeTypes['0.14.0.0.0'];
        $this->assertInstanceOf(Category::class, $placeType);
        $this->assertEquals('0.14.0.0.0', $placeType->getId()->toString());
        $this->assertEquals('Monument', $placeType->getLabel()->toString());
        $this->assertEquals(CategoryDomain::eventType(), $placeType->getDomain());
    }

    /**
     * @test
     */
    public function it_returns_place_facilities_filtered_by_domain_and_scope(): void
    {
        $placeFacilities = $this->client->getPlaceFacilities();

        $this->assertIsArray($placeFacilities);
        $this->assertCount(2, $placeFacilities);
        $this->assertArrayHasKey('3.13.1.0.0', $placeFacilities);
        $this->assertArrayHasKey('3.27.0.0.0', $placeFacilities);

        $facility1 = $placeFacilities['3.13.1.0.0'];
        $this->assertEquals('Voorzieningen voor assistentiehonden', $facility1->getLabel()->toString());

        $facility2 = $placeFacilities['3.27.0.0.0'];
        $this->assertEquals('Rolstoeltoegankelijk', $facility2->getLabel()->toString());
    }

    /**
     * @test
     */
    public function it_returns_empty_array_when_no_terms_match_filter(): void
    {
        $termsData = [
            'terms' => [
                [
                    'id' => '0.50.4.0.0',
                    'domain' => 'eventtype',
                    'scope' => ['events'],
                    'name' => ['nl' => 'Concert'],
                ],
            ],
        ];

        $responseBody = $this->createMock(StreamInterface::class);
        $responseBody->method('getContents')
            ->willReturn(json_encode($termsData));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')
            ->willReturn($responseBody);

        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')
            ->willReturn($response);

        $client = new JsonTaxonomyApiClient($httpClient, $this->termsEndpoint);

        $eventThemes = $client->getEventThemes();
        $this->assertIsArray($eventThemes);
        $this->assertCount(0, $eventThemes);
    }
}
