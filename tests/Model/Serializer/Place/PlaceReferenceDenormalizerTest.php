<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\Place;

use CultuurNet\UDB3\Model\Place\PlaceIDParser;
use CultuurNet\UDB3\Model\Place\PlaceReference;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\TestCase;

class PlaceReferenceDenormalizerTest extends TestCase
{
    private PlaceReferenceDenormalizer $denormalizer;

    public function setUp(): void
    {
        $this->denormalizer = new PlaceReferenceDenormalizer(new PlaceIDParser());
    }

    /**
     * @test
     */
    public function it_should_return_a_place_reference_with_a_place_id(): void
    {
        $data = [
            '@id' => 'https://io.uitdatabank.be/places/ebe48c5f-5d3d-4fc3-a138-0037ab0fbc08',
        ];

        $reference = $this->denormalizer->denormalize($data, PlaceReference::class);

        $expected = new UUID('ebe48c5f-5d3d-4fc3-a138-0037ab0fbc08');
        $actual = $reference->getPlaceId();

        $this->assertEquals($expected, $actual);
    }
}
