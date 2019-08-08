<?php

namespace CultuurNet\UDB3\Http\Deserializer\Place;

use CultuurNet\Deserializer\DataValidationException;
use CultuurNet\UDB3\Facility;
use CultuurNet\UDB3\Offer\OfferFacilityResolverInterface;
use CultuurNet\UDB3\Place\PlaceFacilityResolver;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class FacilitiesJSONDeserializerTest extends TestCase
{
    /**
     * @var OfferFacilityResolverInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $facilityResolver;

    public function setUp()
    {
        $this->facilityResolver = $this->createMock(OfferFacilityResolverInterface::class);
    }

    /**
     * @test
     * @throws DataValidationException
     */
    public function it_should_not_accept_data_without_a_list_of_facility_ids()
    {
        $deserializer = new FacilitiesJSONDeserializer($this->facilityResolver);

        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('The facilities property should contain a list of ids');

        $deserializer->deserialize(new StringLiteral('{"facilities": "C92E4A28-4A59-43DD-999E-7F53F735D30C"}'));
    }

    /**
     * @test
     * @throws DataValidationException
     */
    public function it_should_return_a_facilities_list_from_valid_data_with_a_single_facility()
    {
        $deserializer = new FacilitiesJSONDeserializer($this->facilityResolver);
        $facility = new Facility("3.23.1.0.0", "Voorzieningen voor rolstoelgebruikers");
        $this->facilityResolver->expects($this->once())
            ->method('byId')
            ->with('3.23.1.0.0')
            ->willReturn($facility);

        $expectedFacilities = [$facility];

        $facilities = $deserializer->deserialize(new StringLiteral(
            '{"facilities": ["3.23.1.0.0"]}'
        ));

        $this->assertEquals($expectedFacilities, $facilities);
    }

    /**
     * @test
     * @throws DataValidationException
     */
    public function it_should_return_a_facilities_list_without_duplicates()
    {
        $deserializer = new FacilitiesJSONDeserializer($this->facilityResolver);
        $facility = new Facility("3.23.1.0.0", "Voorzieningen voor rolstoelgebruikers");
        $this->facilityResolver->expects($this->once())
            ->method('byId')
            ->with('3.23.1.0.0')
            ->willReturn($facility);

        $expectedFacilities = [$facility];

        $facilities = $deserializer->deserialize(new StringLiteral(
            '{"facilities": ["3.23.1.0.0", "3.23.1.0.0"]}'
        ));

        $this->assertEquals($expectedFacilities, $facilities);
    }

    /**
     * @test
     * @throws DataValidationException
     */
    public function it_should_return_a_facilities_list_from_valid_data_with_multiple_facilities()
    {
        $deserializer = new FacilitiesJSONDeserializer(new PlaceFacilityResolver());
        $wheelchairFacility = new Facility("3.13.1.0.0", "Voorzieningen voor assistentiehonden");
        $audioDescriptionFacility = new Facility("3.25.0.0.0", "Contactpunt voor personen met handicap");

        $expectedFacilities = [$wheelchairFacility, $audioDescriptionFacility];

        $facilities = $deserializer->deserialize(new StringLiteral(
            '{"facilities": ["3.13.1.0.0", "3.25.0.0.0"]}'
        ));

        $this->assertEquals($expectedFacilities, $facilities);
    }

    /**
     * @test
     */
    public function it_should_not_deserialize_unresolvable_facility_ids()
    {
        $deserializer = new FacilitiesJSONDeserializer(new PlaceFacilityResolver());

        $this->expectExceptionMessage("Unknown facility id '1.8.2'");

        $deserializer->deserialize(new StringLiteral(
            '{"facilities": ["3.25.0.0.0", "1.8.2"]}'
        ));
    }
}
