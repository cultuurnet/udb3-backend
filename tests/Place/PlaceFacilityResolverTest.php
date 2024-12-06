<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
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
        $expectedFacility = new Category(new CategoryID('3.23.3.0.0'), new CategoryLabel('Rolstoel ter beschikking'), CategoryDomain::facility());

        $this->assertEquals($expectedFacility, $facility);
    }
}
