<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Storage;

use PHPUnit\Framework\TestCase;

class PurgeServiceManagerTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_store_an_array_of_PurgeServiceInterfaces()
    {
        $purgeServiceManager = new PurgeServiceManager([
            $this->createMock(PurgeServiceInterface::class),
            $this->createMock(PurgeServiceInterface::class),
        ]);

        $this->assertCount(2, $purgeServiceManager->getPurgeServices());
    }
}
