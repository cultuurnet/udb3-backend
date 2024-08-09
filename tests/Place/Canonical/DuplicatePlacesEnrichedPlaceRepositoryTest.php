<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DuplicatePlacesEnrichedPlaceRepositoryTest extends TestCase
{
    private DuplicatePlacesEnrichedPlaceRepository $duplicatePlacesEnrichedPlaceRepository;

    /** @var DuplicatePlaceRepository&MockObject */
    private $duplicatePlaceRepository;

    private InMemoryDocumentRepository $decoratedRepository;

    protected function setUp(): void
    {
        $this->duplicatePlaceRepository = $this->createMock(DuplicatePlaceRepository::class);

        $this->decoratedRepository = new InMemoryDocumentRepository();

        $this->duplicatePlacesEnrichedPlaceRepository = new DuplicatePlacesEnrichedPlaceRepository(
            $this->duplicatePlaceRepository,
            new CallableIriGenerator(fn (string $placeId) => 'https://io.uitdatabank.dev/places/' . $placeId),
            $this->decoratedRepository
        );
    }

    /**
     * @test
     */
    public function it_clears_duplicate_properties_from_a_regular_place(): void
    {
        $placeId = '2f2b5d2b-a360-4891-aa2e-c2b785e61dc8';

        $jsonDocument = new JsonDocument(
            $placeId,
            Json::encode([
                '@type' => 'Place',
                'duplicateOf' => 'https://io.uitdatabank.dev/places/9144a298-0d1a-45ec-8630-4c4326ebb502',
                'duplicatedBy' => [
                    'https://io.uitdatabank.dev/places/7c652059-dcb0-4dee-9694-ff555671f4e9',
                    'https://io.uitdatabank.dev/places/dca24458-f3a9-42c3-923a-2b9193a5f778',
                ],
            ])
        );
        $this->decoratedRepository->save($jsonDocument);

        $this->duplicatePlaceRepository->expects($this->once())
            ->method('getCanonicalOfPlace')
            ->with($placeId)
            ->willReturn(null);

        $this->duplicatePlaceRepository->expects($this->once())
            ->method('getDuplicatesOfPlace')
            ->with($placeId)
            ->willReturn(null);

        $fetchedDocument = $this->duplicatePlacesEnrichedPlaceRepository->fetch($placeId);

        $this->assertEquals(
            new JsonDocument(
                $placeId,
                Json::encode([
                    '@type' => 'Place',
                ])
            ),
            $fetchedDocument
        );
    }

    /**
     * @test
     */
    public function it_sets_duplicate_of_on_duplicate_place(): void
    {
        $placeId = '2f2b5d2b-a360-4891-aa2e-c2b785e61dc8';

        $jsonDocument = new JsonDocument(
            $placeId,
            Json::encode([
                '@type' => 'Place',
            ])
        );
        $this->decoratedRepository->save($jsonDocument);

        $this->duplicatePlaceRepository->expects($this->once())
            ->method('getCanonicalOfPlace')
            ->with($placeId)
            ->willReturn('862fb956-42d4-42cd-809f-e7ee82d14018');

        $this->duplicatePlaceRepository->expects($this->once())
            ->method('getDuplicatesOfPlace')
            ->with($placeId)
            ->willReturn(null);

        $fetchedDocument = $this->duplicatePlacesEnrichedPlaceRepository->fetch($placeId);

        $this->assertEquals(
            new JsonDocument(
                $placeId,
                Json::encode([
                    '@type' => 'Place',
                    'duplicateOf' => 'https://io.uitdatabank.dev/places/862fb956-42d4-42cd-809f-e7ee82d14018',
                ])
            ),
            $fetchedDocument
        );
    }

    /**
     * @test
     */
    public function it_sets_duplicated_by_on_canonical_place(): void
    {
        $placeId = '2f2b5d2b-a360-4891-aa2e-c2b785e61dc8';

        $jsonDocument = new JsonDocument(
            $placeId,
            Json::encode([
                '@type' => 'Place',
            ])
        );
        $this->decoratedRepository->save($jsonDocument);

        $this->duplicatePlaceRepository->expects($this->once())
            ->method('getCanonicalOfPlace')
            ->with($placeId)
            ->willReturn(null);

        $this->duplicatePlaceRepository->expects($this->once())
            ->method('getDuplicatesOfPlace')
            ->with($placeId)
            ->willReturn([
                'dc9b6bf8-a6ba-4f05-87e4-6bd5e8ef84ab',
                '9a50c68c-cdb8-4edc-bd59-9d5a98697980',
            ]);

        $fetchedDocument = $this->duplicatePlacesEnrichedPlaceRepository->fetch($placeId);

        $this->assertEquals(
            new JsonDocument(
                $placeId,
                Json::encode([
                    '@type' => 'Place',
                    'duplicatedBy' => [
                        'https://io.uitdatabank.dev/places/dc9b6bf8-a6ba-4f05-87e4-6bd5e8ef84ab',
                        'https://io.uitdatabank.dev/places/9a50c68c-cdb8-4edc-bd59-9d5a98697980',
                    ],
                ])
            ),
            $fetchedDocument
        );
    }

    /**
     * @test
     */
    public function it_throws_when_place_is_not_found(): void
    {
        $this->expectException(DocumentDoesNotExist::class);

        $this->duplicatePlacesEnrichedPlaceRepository->fetch('94f42f91-20ab-4a1f-a574-9d5755cc0276');
    }
}
