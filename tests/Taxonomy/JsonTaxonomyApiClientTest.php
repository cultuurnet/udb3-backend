<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Taxonomy;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Log\NullLogger;

final class JsonTaxonomyApiClientTest extends TestCase
{
    private ClientInterface&MockObject $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
    }

    private function createClientWithTerms(array $terms): JsonTaxonomyApiClient
    {
        $this->httpClient->expects($this->once())
                   ->method('sendRequest')
                   ->willReturn(new Response(200, [], Json::encode(['terms' => $terms])));

        return new JsonTaxonomyApiClient($this->httpClient, 'https://taxonomy.example.com/terms', new NullLogger());
    }

    /**
     * @test
     */
    public function it_throws_when_api_returns_non_200_status(): void
    {
        $this->httpClient->expects($this->once())
                   ->method('sendRequest')
                   ->willReturn(new Response(503));

        $this->expectException(TaxonomyApiProblem::class);

        new JsonTaxonomyApiClient($this->httpClient, 'https://taxonomy.example.com/terms', new NullLogger());
    }

    /**
     * @test
     */
    public function it_throws_when_api_returns_empty_body(): void
    {
        $this->httpClient->expects($this->once())
                   ->method('sendRequest')
                   ->willReturn(new Response(200, [], ''));

        $this->expectException(TaxonomyApiProblem::class);

        new JsonTaxonomyApiClient($this->httpClient, 'https://taxonomy.example.com/terms', new NullLogger());
    }

    /**
     * @test
     */
    public function it_returns_event_types(): void
    {
        $client = $this->createClientWithTerms([
                       ['id' => '0.50.4.0.0', 'domain' => 'eventtype', 'name' => ['nl' => 'Concert'], 'scope' => ['events']],
                       ['id' => '3CuHvenJ+EGkcvhXLg9Ykg', 'domain' => 'eventtype', 'name' => ['nl' => 'Tentoonstelling'], 'scope' => ['places']],
                       ['id' => '1.8.1.0.0', 'domain' => 'facility', 'name' => ['nl' => 'Ringleiding'], 'scope' => ['events']],
                   ]);

        $this->assertEquals(
            new Categories(
                new Category(
                    new CategoryID('0.50.4.0.0'),
                    new CategoryLabel('Concert'),
                    CategoryDomain::eventType()
                )
            ),
            $client->getEventTypes()
        );
    }

    /**
     * @test
     */
    public function it_returns_event_themes(): void
    {
        $client = $this->createClientWithTerms([
                       ['id' => '1.8.3.1.0', 'domain' => 'theme', 'name' => ['nl' => 'Jazz en blues'], 'scope' => ['events']],
                       ['id' => '0.50.4.0.0', 'domain' => 'eventtype', 'name' => ['nl' => 'Concert'], 'scope' => ['events']],
                   ]);

        $this->assertEquals(
            new Categories(
                new Category(
                    new CategoryID('1.8.3.1.0'),
                    new CategoryLabel('Jazz en blues'),
                    CategoryDomain::theme()
                )
            ),
            $client->getEventThemes()
        );
    }

    /**
     * @test
     */
    public function it_returns_event_facilities(): void
    {
        $client = $this->createClientWithTerms([
                      ['id' => '1.8.1.0.0', 'domain' => 'facility', 'name' => ['nl' => 'Ringleiding'], 'scope' => ['events', 'places']],
                      ['id' => '1.9.0.0.0', 'domain' => 'facility', 'name' => ['nl' => 'Parkeerplaats'], 'scope' => ['places']],
                      ['id' => '0.50.4.0.0', 'domain' => 'eventtype', 'name' => ['nl' => 'Concert'], 'scope' => ['events']],
                  ]);

        $this->assertEquals(
            new Categories(
                new Category(
                    new CategoryID('1.8.1.0.0'),
                    new CategoryLabel('Ringleiding'),
                    CategoryDomain::facility()
                )
            ),
            $client->getEventFacilities()
        );
    }

    /**
       * @test
       */
    public function it_returns_place_types(): void
    {
        $client = $this->createClientWithTerms([
                      ['id' => '3CuHvenJ+EGkcvhXLg9Ykg', 'domain' => 'eventtype', 'name' => ['nl' => 'Tentoonstelling'], 'scope' => ['places']],
                      ['id' => '0.50.4.0.0', 'domain' => 'eventtype', 'name' => ['nl' => 'Concert'], 'scope' => ['events']],
                      ['id' => '1.8.1.0.0', 'domain' => 'facility', 'name' => ['nl' => 'Ringleiding'], 'scope' => ['places']],
                  ]);

        $this->assertEquals(
            new Categories(
                new Category(
                    new CategoryID('3CuHvenJ+EGkcvhXLg9Ykg'),
                    new CategoryLabel('Tentoonstelling'),
                    CategoryDomain::eventType()
                )
            ),
            $client->getPlaceTypes()
        );
    }

    /**
     * @test
     */
    public function it_returns_place_facilities(): void
    {
        $client = $this->createClientWithTerms([
                      ['id' => '1.8.1.0.0', 'domain' => 'facility', 'name' => ['nl' => 'Ringleiding'], 'scope' => ['events', 'places']],
                      ['id' => '1.9.0.0.0', 'domain' => 'facility', 'name' => ['nl' => 'Parkeerplaats'], 'scope' => ['places']],
                      ['id' => '0.50.4.0.0', 'domain' => 'eventtype', 'name' => ['nl' => 'Concert'], 'scope' => ['events']],
                  ]);

        $this->assertEquals(
            new Categories(
                new Category(
                    new CategoryID('1.8.1.0.0'),
                    new CategoryLabel('Ringleiding'),
                    CategoryDomain::facility()
                ),
                new Category(
                    new CategoryID('1.9.0.0.0'),
                    new CategoryLabel('Parkeerplaats'),
                    CategoryDomain::facility()
                )
            ),
            $client->getPlaceFacilities()
        );
    }

    /**
     * @test
     */
    public function it_returns_an_empty_list_when_no_terms_match(): void
    {
        $client = $this->createClientWithTerms([
                      ['id' => '0.50.4.0.0', 'domain' => 'eventtype', 'name' => ['nl' => 'Concert'], 'scope' => ['events']],
                  ]);

        $this->assertEquals(new Categories(), $client->getEventThemes());
    }

    /**
     * @test
     */
    public function it_returns_native_terms(): void
    {
        $terms = [
                      ['id' => '0.50.4.0.0', 'domain' => 'eventtype', 'name' => ['nl' => 'Concert'], 'scope' => ['events']],
                  ];

        $this->assertEquals($terms, $this->createClientWithTerms($terms)->getNativeTerms());
    }
}
