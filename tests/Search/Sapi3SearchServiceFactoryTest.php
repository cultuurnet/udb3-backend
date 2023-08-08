<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use League\Container\DefinitionContainerInterface;
use PHPUnit\Framework\TestCase;

class Sapi3SearchServiceFactoryTest extends TestCase
{
    private DefinitionContainerInterface $container;

    public function setUp(): void
    {
        $services = [
            'config' => [
                'search' => ['v3' => ['base_url' => '']],
                'item_url_regex' => '',
                'export' => ['search' => ['api_key' => '']],
            ],
        ];

        $this->container = $this->createMock(DefinitionContainerInterface::class);
        $this->container
            ->method('get')
            ->willReturnCallback(
                function (string $id) use ($services) {
                    return $services[$id] ?? null;
                }
            );
    }

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function it_returns_event_search_service(string $expectedClass, SearchServiceInterface $searchService): void
    {
        $this->assertInstanceOf($expectedClass, $searchService);
    }

    public function dataProvider(): array
    {
        $this->setUp();

        return [
            [EventsSapi3SearchService::class, SearchSapi3ServiceFactory::createEventsSearchService($this->container)],
            [PlacesSapi3SearchService::class, SearchSapi3ServiceFactory::createPlacesSearchService($this->container)],
            [OffersSapi3SearchService::class, SearchSapi3ServiceFactory::createOffersSearchService($this->container)],
            [OrganizersSapi3SearchService::class, SearchSapi3ServiceFactory::createOrganizerSearchService($this->container)],
        ];
    }
}
