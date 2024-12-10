<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\Place;

use CultuurNet\UDB3\Model\Place\PlaceIDParser;
use CultuurNet\UDB3\Model\Place\PlaceReference;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
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
    public function it_returns_a_place_reference_with_a_place_id(): void
    {
        $data = [
            '@id' => 'https://io.uitdatabank.be/places/ebe48c5f-5d3d-4fc3-a138-0037ab0fbc08',
        ];

        $reference = $this->denormalizer->denormalize($data, PlaceReference::class);

        $this->assertEquals(
            new UUID('ebe48c5f-5d3d-4fc3-a138-0037ab0fbc08'),
            $reference->getPlaceId()
        );
        $this->assertNull($reference->getAddress());
    }

    /**
     * @test
     */
    public function it_returns_an_address_for_a_dummy_location(): void
    {
        $data = [
            'address' => [
                'nl' => [
                    'addressCountry' => 'BE',
                    'addressLocality' => 'Leuven',
                    'postalCode' => '3000',
                    'streetAddress' => 'Brusselsestraat 63',
                ],
            ],
        ];

        $reference = $this->denormalizer->denormalize($data, PlaceReference::class);

        $this->assertNull($reference->getPlaceId());
        $this->assertEquals(
            new TranslatedAddress(
                new Language('nl'),
                new Address(
                    new Street('Brusselsestraat 63'),
                    new PostalCode('3000'),
                    new Locality('Leuven'),
                    new CountryCode('BE')
                )
            ),
            $reference->getAddress()
        );
    }
}
