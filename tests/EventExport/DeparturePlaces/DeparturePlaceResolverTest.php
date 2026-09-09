<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\DeparturePlaces;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

final class DeparturePlaceResolverTest extends TestCase
{
    private DocumentRepository&MockObject $placeRepository;

    private DeparturePlaceResolver $resolver;

    protected function setUp(): void
    {
        $this->placeRepository = $this->createMock(DocumentRepository::class);
        $this->resolver = new DeparturePlaceResolver($this->placeRepository);
    }

    /**
     * @test
     */
    public function it_describes_every_departure_place(): void
    {
        $this->givenPlaces([
            'abc-123' => [
                'name' => ['nl' => 'Centraal Station'],
                'address' => ['nl' => ['postalCode' => '2000', 'addressLocality' => 'Antwerpen']],
            ],
            'def-456' => [
                'name' => ['nl' => 'Sint-Pietersplein'],
                'address' => ['nl' => ['postalCode' => '9000', 'addressLocality' => 'Gent']],
            ],
        ]);

        $departurePlaces = $this->resolver->resolve($this->event(['abc-123', 'def-456']));

        $this->assertEquals(
            [
                new DeparturePlace('Centraal Station', '2000', 'Antwerpen'),
                new DeparturePlace('Sint-Pietersplein', '9000', 'Gent'),
            ],
            $departurePlaces
        );
    }

    /**
     * @test
     */
    public function it_reads_a_place_in_its_own_main_language(): void
    {
        $this->givenPlaces([
            'abc-123' => [
                'mainLanguage' => 'fr',
                'name' => ['nl' => 'Zuidstation', 'fr' => 'Gare du Midi'],
                'address' => [
                    'nl' => ['postalCode' => '1060', 'addressLocality' => 'Sint-Gillis'],
                    'fr' => ['postalCode' => '1060', 'addressLocality' => 'Saint-Gilles'],
                ],
            ],
        ]);

        $this->assertEquals(
            [new DeparturePlace('Gare du Midi', '1060', 'Saint-Gilles')],
            $this->resolver->resolve($this->event(['abc-123']))
        );
    }

    /**
     * @test
     */
    public function it_falls_back_to_the_only_translation_a_place_has(): void
    {
        $this->givenPlaces([
            'abc-123' => [
                'mainLanguage' => 'de',
                'name' => ['fr' => 'Gare du Midi'],
                'address' => ['fr' => ['postalCode' => '1060', 'addressLocality' => 'Saint-Gilles']],
            ],
        ]);

        $this->assertEquals(
            [new DeparturePlace('Gare du Midi', '1060', 'Saint-Gilles')],
            $this->resolver->resolve($this->event(['abc-123']))
        );
    }

    /**
     * @test
     */
    public function it_reads_an_address_that_is_not_translated(): void
    {
        $this->givenPlaces([
            'abc-123' => [
                'name' => 'Centraal Station',
                'address' => ['postalCode' => '2000', 'addressLocality' => 'Antwerpen'],
            ],
        ]);

        $this->assertEquals(
            [new DeparturePlace('Centraal Station', '2000', 'Antwerpen')],
            $this->resolver->resolve($this->event(['abc-123']))
        );
    }

    /**
     * @test
     */
    public function it_skips_a_place_that_no_longer_exists(): void
    {
        $this->placeRepository->method('fetch')->willReturnCallback(
            function (string $id): JsonDocument {
                if ($id === 'gone-999') {
                    throw DocumentDoesNotExist::withId($id);
                }

                return new JsonDocument($id, Json::encode([
                    'name' => ['nl' => 'Centraal Station'],
                    'address' => ['nl' => ['postalCode' => '2000', 'addressLocality' => 'Antwerpen']],
                ]));
            }
        );

        $this->assertEquals(
            [new DeparturePlace('Centraal Station', '2000', 'Antwerpen')],
            $this->resolver->resolve($this->event(['gone-999', 'abc-123']))
        );
    }

    /**
     * @test
     */
    public function it_describes_a_place_that_is_missing_an_address(): void
    {
        $this->givenPlaces(['abc-123' => ['name' => ['nl' => 'Centraal Station']]]);

        $this->assertEquals(
            [new DeparturePlace('Centraal Station', '', '')],
            $this->resolver->resolve($this->event(['abc-123']))
        );
    }

    /**
     * @test
     * @dataProvider eventsWithoutDeparturePlaces
     */
    public function it_describes_nothing_without_departure_places(array $properties): void
    {
        $this->placeRepository->expects($this->never())->method('fetch');

        $event = Json::decode(Json::encode((object) $properties));

        $this->assertSame([], $this->resolver->resolve($event));
    }

    public function eventsWithoutDeparturePlaces(): array
    {
        return [
            'no departure places at all' => ['properties' => []],
            'an empty list' => ['properties' => ['departurePlaces' => []]],
            'a list of something other than urls' => ['properties' => ['departurePlaces' => [12, null]]],
        ];
    }

    private function givenPlaces(array $places): void
    {
        $this->placeRepository->method('fetch')->willReturnCallback(
            fn (string $id): JsonDocument => new JsonDocument($id, Json::encode($places[$id]))
        );
    }

    private function event(array $placeIds): stdClass
    {
        return Json::decode(Json::encode([
            'departurePlaces' => array_map(
                fn (string $id): string => 'https://io.uitdatabank.be/place/' . $id,
                $placeIds
            ),
        ]));
    }
}
