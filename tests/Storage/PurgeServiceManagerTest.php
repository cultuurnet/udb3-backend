<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Storage;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PurgeServiceManagerTest extends TestCase
{
    /**
     * @var PurgeServiceManager
     */
    private $purgeServiceManager;

    protected function setUp()
    {
        $this->purgeServiceManager = new PurgeServiceManager();
    }

    /**
     * @test
     */
    public function it_can_store_an_array_of_PurgeServiceInterfaces()
    {
        $this->addReadModel_PurgeServiceInterfaces($this->purgeServiceManager);

        $this->assertCount(2, $this->purgeServiceManager->getPurgeServices());
    }


    private function addReadModel_PurgeServiceInterfaces(PurgeServiceManager $purgeServiceManager)
    {
        $purgeServiceManager->addPurgeService($this->createMockedPurgeService());
        $purgeServiceManager->addPurgeService($this->createMockedPurgeService());
    }

    /**
     * @return PurgeServiceInterface|MockObject
     */
    private function createMockedPurgeService()
    {
        return $this->createMock(PurgeServiceInterface::class);
    }
}
