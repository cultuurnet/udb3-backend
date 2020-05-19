<?php

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Facility;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class PlaceFacilityResolverTest extends TestCase
{
    /**
     * @test
     * @throws \Exception
     */
    public function it_should_not_resolve_a_facility_when_the_id_is_unknown()
    {
        $resolver = new PlaceFacilityResolver();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Unknown facility id '1.8.2'");

        $resolver->byId(new StringLiteral('1.8.2'));
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_the_matching_facility_when_passed_a_known_id()
    {
        $resolver = new PlaceFacilityResolver();

        $facility = $resolver->byId(new StringLiteral('3.23.3.0.0'));
        $expectedFacility = new Facility("3.23.3.0.0", "Rolstoel ter beschikking");

        $this->assertEquals($expectedFacility, $facility);
    }
}
