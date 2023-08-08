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
    public function it_returns_event_search_service($expectedClass, $searchService): void
    {
        $this->assertInstanceOf($expectedClass, $searchService);
    }

    public function dataProvider(): array
    {
        $this->setUp();

        return [
            [Sapi3EventsSearchService::class, Sapi3SearchServiceFactory::createEventsSearchService($this->container)],
            [Sapi3PlacesSearchService::class, Sapi3SearchServiceFactory::createPlacesSearchService($this->container)],
            [Sapi3OffersSearchService::class, Sapi3SearchServiceFactory::createOffersSearchService($this->container)],
            [Sapi3OrganizersSearchService::class, Sapi3SearchServiceFactory::createOrganizerSearchService($this->container)],
        ];
    }
}
