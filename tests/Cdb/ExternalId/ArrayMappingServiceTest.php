<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cdb\ExternalId;

use PHPUnit\Framework\TestCase;

class ArrayMappingServiceTest extends TestCase
{
    private ArrayMappingService $mappingService;

    public function setUp(): void
    {
        $array = [
            'SKB Import:Organisation_6666' => 'b91a72d0-7d69-4fe2-81f4-5c0f29001089',
            'TA_productie:99999' => '49cedb90-ef38-4612-b958-c49d3d1c8b83',
            'ccbelgica_Organiser_1' => 'b91a72d0-7d69-4fe2-81f4-5c0f29001089',
        ];

        $this->mappingService = new ArrayMappingService($array);
    }

    /**
     * @test
     */
    public function it_returns_the_cdbid_of_a_known_external_id(): void
    {
        $this->assertEquals(
            'b91a72d0-7d69-4fe2-81f4-5c0f29001089',
            $this->mappingService->getCdbId('SKB Import:Organisation_6666')
        );
    }

    /**
     * @test
     */
    public function it_can_map_multiple_external_ids_to_the_same_cdbid(): void
    {
        $cdbid = 'b91a72d0-7d69-4fe2-81f4-5c0f29001089';

        $this->assertEquals($cdbid, $this->mappingService->getCdbId('SKB Import:Organisation_6666'));
        $this->assertEquals($cdbid, $this->mappingService->getCdbId('ccbelgica_Organiser_1'));
    }

    /**
     * @test
     */
    public function it_returns_null_for_unknown_external_ids(): void
    {
        $this->assertNull($this->mappingService->getCdbId('unknown-external-id'));
    }
}
