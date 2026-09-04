<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\DeparturePlaces;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;
use stdClass;

final class DeparturePlaceResolverTest extends TestCase
{
    private const SPORTCENTRUM_ID = '85b04295-479c-40f5-b3dd-469dfb4387b3';

    private const SPORTCENTRUM_URL = 'https://io.uitdatabank.be/places/85b04295-479c-40f5-b3dd-469dfb4387b3';

    private const JEUGDHUIS_ID = '5cf42d51-3a4f-46f0-a8af-1cf672be8c84';

    private const JEUGDHUIS_URL = 'https://io.uitdatabank.be/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84';

    private InMemoryDocumentRepository $placeRepository;

    private DeparturePlaceResolver $resolver;

    protected function setUp(): void
    {
        $this->placeRepository = new InMemoryDocumentRepository();
        $this->resolver = new DeparturePlaceResolver($this->placeRepository);
    }

    /**
     * @test
     */
    public function it_resolves_every_departure_place_in_order(): void
    {
        $this->givenPlace(self::SPORTCENTRUM_ID, [
            'name' => ['nl' => 'Sportcentrum'],
            'address' => ['nl' => ['postalCode' => '3000', 'addressLocality' => 'Leuven']],
        ]);
        $this->givenPlace(self::JEUGDHUIS_ID, [
            'name' => ['nl' => 'Jeugdhuis'],
            'address' => ['nl' => ['postalCode' => '2000', 'addressLocality' => 'Antwerpen']],
        ]);

        $departurePlaces = $this->resolver->resolve(
            $this->event([self::JEUGDHUIS_URL, self::SPORTCENTRUM_URL])
        );

        $this->assertEquals(
            [
                new DeparturePlace(self::JEUGDHUIS_URL, 'Jeugdhuis', '2000', 'Antwerpen'),
                new DeparturePlace(self::SPORTCENTRUM_URL, 'Sportcentrum', '3000', 'Leuven'),
            ],
            $departurePlaces
        );
    }

    /**
     * @test
     */
    public function it_reads_the_name_and_address_in_the_main_language_of_the_place(): void
    {
        $this->givenPlace(self::SPORTCENTRUM_ID, [
            'mainLanguage' => 'fr',
            'name' => ['nl' => 'Sportcentrum', 'fr' => 'Centre sportif'],
            'address' => [
                'nl' => ['postalCode' => '3000', 'addressLocality' => 'Leuven'],
                'fr' => ['postalCode' => '3000', 'addressLocality' => 'Louvain'],
            ],
        ]);

        $this->assertEquals(
            [new DeparturePlace(self::SPORTCENTRUM_URL, 'Centre sportif', '3000', 'Louvain')],
            $this->resolver->resolve($this->event([self::SPORTCENTRUM_URL]))
        );
    }

    /**
     * @test
     */
    public function it_falls_back_to_the_only_available_translation(): void
    {
        $this->givenPlace(self::SPORTCENTRUM_ID, [
            'mainLanguage' => 'de',
            'name' => ['fr' => 'Centre sportif'],
            'address' => ['fr' => ['postalCode' => '3000', 'addressLocality' => 'Louvain']],
        ]);

        $this->assertEquals(
            [new DeparturePlace(self::SPORTCENTRUM_URL, 'Centre sportif', '3000', 'Louvain')],
            $this->resolver->resolve($this->event([self::SPORTCENTRUM_URL]))
        );
    }

    /**
     * @test
     */
    public function it_reads_an_address_that_is_not_grouped_per_language(): void
    {
        $this->givenPlace(self::SPORTCENTRUM_ID, [
            'name' => ['nl' => 'Sportcentrum'],
            'address' => ['postalCode' => '3000', 'addressLocality' => 'Leuven'],
        ]);

        $this->assertEquals(
            [new DeparturePlace(self::SPORTCENTRUM_URL, 'Sportcentrum', '3000', 'Leuven')],
            $this->resolver->resolve($this->event([self::SPORTCENTRUM_URL]))
        );
    }

    /**
     * @test
     */
    public function it_skips_a_place_that_no_longer_exists(): void
    {
        $this->givenPlace(self::SPORTCENTRUM_ID, [
            'name' => ['nl' => 'Sportcentrum'],
            'address' => ['nl' => ['postalCode' => '3000', 'addressLocality' => 'Leuven']],
        ]);

        $departurePlaces = $this->resolver->resolve(
            $this->event([self::JEUGDHUIS_URL, self::SPORTCENTRUM_URL])
        );

        $this->assertEquals(
            [new DeparturePlace(self::SPORTCENTRUM_URL, 'Sportcentrum', '3000', 'Leuven')],
            $departurePlaces
        );
    }

    /**
     * @test
     */
    public function it_resolves_a_place_without_a_name_or_an_address(): void
    {
        $this->givenPlace(self::SPORTCENTRUM_ID, []);

        $this->assertEquals(
            [new DeparturePlace(self::SPORTCENTRUM_URL, '', '', '')],
            $this->resolver->resolve($this->event([self::SPORTCENTRUM_URL]))
        );
    }

    /**
     * @test
     */
    public function it_ignores_an_event_without_departure_places(): void
    {
        $this->assertSame([], $this->resolver->resolve(new stdClass()));
    }

    /**
     * @test
     */
    public function it_ignores_departure_places_that_are_not_urls(): void
    {
        $this->assertSame([], $this->resolver->resolve($this->event([['not' => 'a url'], ''])));
    }

    /**
     * @test
     */
    public function it_resolves_nothing_without_a_place_repository(): void
    {
        $resolver = new DeparturePlaceResolver();

        $this->assertSame([], $resolver->resolve($this->event([self::SPORTCENTRUM_URL])));
    }

    private function givenPlace(string $placeId, array $place): void
    {
        $this->placeRepository->save(
            new JsonDocument($placeId, Json::encode(['@id' => $placeId] + $place))
        );
    }

    private function event(array $departurePlaces): stdClass
    {
        $event = new stdClass();
        $event->departurePlaces = $departurePlaces;

        return $event;
    }
}
