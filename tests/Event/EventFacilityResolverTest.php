<?php

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Facility;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class EventFacilityResolverTest extends TestCase
{
    /**
     * @test
     * @throws \Exception
     */
    public function it_should_not_resolve_a_facility_when_the_id_is_unknown()
    {
        $resolver = new EventFacilityResolver();

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
        $resolver = new EventFacilityResolver();

        $facility = $resolver->byId(new StringLiteral('3.13.2.0.0'));
        $expectedFacility = new Facility("3.13.2.0.0", "Audiodescriptie");

        $this->assertEquals($expectedFacility, $facility);
    }
}
