<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Model\Place\ImmutablePlace;
use CultuurNet\UDB3\Model\Serializer\Place\NilLocationNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class NilLocationEnrichedPlaceRepositoryTest extends TestCase
{
    private NilLocationEnrichedPlaceRepository $nilLocationEnrichedPlaceRepository;

    /** @var DocumentRepository&MockObject  */
    private $placeRepository;

    protected function setUp(): void
    {
        $this->placeRepository = $this->createMock(DocumentRepository::class);

        $this->nilLocationEnrichedPlaceRepository = new NilLocationEnrichedPlaceRepository(
            new NilLocationNormalizer(new CallableIriGenerator(
                fn (string $id) => 'https://io.uitdatabank.dev/places/' . $id
            )),
            $this->placeRepository
        );
    }

    /**
     * @test
     */
    public function it_returns_nil_location_for_nil_location_id(): void
    {
        $this->placeRepository->expects($this->never())
            ->method('fetch');

        $place = $this->nilLocationEnrichedPlaceRepository->fetch(UUID::NIL);

        $this->assertEquals(
            (new JsonDocument(UUID::NIL))->withAssocBody(
                (new NilLocationNormalizer(new CallableIriGenerator(
                    fn (string $id) => 'https://io.uitdatabank.dev/places/' . $id
                )))->normalize(ImmutablePlace::createNilLocation())
            ),
            $place
        );
    }

    /**
     * @test
     */
    public function it_delegates_for_non_nil_location_id(): void
    {
        $jsonDocument = (new JsonDocument('a11b7f5c-f662-4db5-a74e-43f4d6c87832'))->withAssocBody(['@type' => 'Place']);

        $this->placeRepository->expects($this->once())
            ->method('fetch')
            ->with('a11b7f5c-f662-4db5-a74e-43f4d6c87832')
            ->willReturn($jsonDocument);

        $place = $this->nilLocationEnrichedPlaceRepository->fetch('a11b7f5c-f662-4db5-a74e-43f4d6c87832');

        $this->assertEquals($jsonDocument, $place);
    }
}
