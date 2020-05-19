<?php

namespace CultuurNet\UDB3\Place\Commands;

use CultuurNet\UDB3\Facility;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;

class UpdateFacilitiesTest extends TestCase
{
    /**
     * @var UpdateFacilities
     */
    protected $updateFacilities;

    public function setUp()
    {
        $facilities = [
            new Facility('facility1', 'facility label'),
        ];

        $this->updateFacilities = new UpdateFacilities(
            'id',
            $facilities
        );
    }

    /**
     * @test
     */
    public function it_returns_the_correct_property_values()
    {
        $expectedId = 'id';
        $expectedFacilities = [
            new Facility('facility1', 'facility label'),
        ];

        $this->assertEquals($expectedId, $this->updateFacilities->getItemId());
        $this->assertEquals($expectedFacilities, $this->updateFacilities->getFacilities());
    }

    /**
     * @test
     */
    public function it_has_special_permission_voorzieningen_bewerken()
    {
        $this->assertEquals(
            Permission::VOORZIENINGEN_BEWERKEN(),
            $this->updateFacilities->getPermission()
        );
    }
}
