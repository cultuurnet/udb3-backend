<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Facility;
use PHPUnit\Framework\TestCase;

final class PlaceFacilityResolverTest extends TestCase
{
    /**
     * @test
     * @throws \Exception
     */
    public function it_should_not_resolve_a_facility_when_the_id_is_unknown(): void
    {
        $resolver = new PlaceFacilityResolver();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Unknown facility id '1.8.2'");

        $resolver->byId('1.8.2');
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_the_matching_facility_when_passed_a_known_id(): void
    {
        $resolver = new PlaceFacilityResolver();

        $facility = $resolver->byId('3.23.3.0.0');
        $expectedFacility = new Facility('3.23.3.0.0', 'Rolstoel ter beschikking');

        $this->assertEquals($expectedFacility, $facility);
    }
}
