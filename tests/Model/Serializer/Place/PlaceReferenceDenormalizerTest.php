<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\Place;

use CultuurNet\UDB3\Model\Place\ImmutablePlace;
use CultuurNet\UDB3\Model\Place\PlaceIDParser;
use CultuurNet\UDB3\Model\Place\PlaceReference;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use PHPUnit\Framework\TestCase;

class PlaceReferenceDenormalizerTest extends TestCase
{
    /**
     * @var PlaceReferenceDenormalizer
     */
    private $denormalizer;

    public function setUp()
    {
        $this->denormalizer = new PlaceReferenceDenormalizer(
            new PlaceIDParser(),
            new PlaceDenormalizer()
        );
    }

    /**
     * @test
     */
    public function it_should_return_a_place_reference_with_a_place_id()
    {
        $data = [
            '@id' => 'https://io.uitdatabank.be/places/ebe48c5f-5d3d-4fc3-a138-0037ab0fbc08',
        ];

        $reference = $this->denormalizer->denormalize($data, PlaceReference::class);

        $expected = new UUID('ebe48c5f-5d3d-4fc3-a138-0037ab0fbc08');
        $actual = $reference->getPlaceId();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_a_place_reference_with_an_embedded_place()
    {
        $data = [
            '@id' => 'https://io.uitdatabank.be/places/ebe48c5f-5d3d-4fc3-a138-0037ab0fbc08',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Voorbeeld naam',
            ],
            'calendarType' => 'permanent',
            'address' => [
                'nl' => [
                    'streetAddress' => 'Henegouwenkaai 41-43',
                    'postalCode' => '1080',
                    'addressLocality' => 'Brussel',
                    'addressCountry' => 'BE',
                ],
            ],
            'terms' => [
                [
                    'id' => '0.14.0.0.0',
                    'label' => 'Monument',
                    'domain' => 'eventtype',
                ],
            ],
        ];

        $reference = $this->denormalizer->denormalize($data, PlaceReference::class);

        $expectedId = new UUID('ebe48c5f-5d3d-4fc3-a138-0037ab0fbc08');
        $actualId = $reference->getPlaceId();

        $expectedPlace = new ImmutablePlace(
            new UUID('ebe48c5f-5d3d-4fc3-a138-0037ab0fbc08'),
            new Language('nl'),
            new TranslatedTitle(new Language('nl'), new Title('Voorbeeld naam')),
            new PermanentCalendar(new OpeningHours()),
            new TranslatedAddress(
                new Language('nl'),
                new Address(
                    new Street('Henegouwenkaai 41-43'),
                    new PostalCode('1080'),
                    new Locality('Brussel'),
                    new CountryCode('BE')
                )
            ),
            new Categories(
                new Category(
                    new CategoryID('0.14.0.0.0'),
                    new CategoryLabel('Monument'),
                    new CategoryDomain('eventtype')
                )
            )
        );
        $actualPlace = $reference->getEmbeddedPlace();

        $this->assertEquals($expectedId, $actualId);
        $this->assertEquals($expectedPlace, $actualPlace);
    }

    /**
     * @test
     */
    public function it_should_return_a_place_reference_with_a_place_id_and_no_embedded_place_if_the_place_was_invalid()
    {
        $data = [
            '@id' => 'https://io.uitdatabank.be/places/ebe48c5f-5d3d-4fc3-a138-0037ab0fbc08',
            'name' => 'Test',
            'address' => [
                'streetAddress' => 'Henegouwenkaai 41-43',
                'postalCode' => '1080',
                'addressLocality' => 'Brussel',
                'addressCountry' => 'BE',
            ],
        ];

        $reference = $this->denormalizer->denormalize($data, PlaceReference::class);

        $expected = new UUID('ebe48c5f-5d3d-4fc3-a138-0037ab0fbc08');
        $actual = $reference->getPlaceId();

        $this->assertEquals($expected, $actual);
        $this->assertNull($reference->getEmbeddedPlace());
    }
}
