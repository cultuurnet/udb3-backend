<?php

namespace CultuurNet\UDB3\Storage;

/**
 * Class PurgeServiceManager
 * @package CultuurNet\UDB3\Storage
 */
class PurgeServiceManager
{
    /**
     * @var PurgeServiceInterface[]
     */
    private $readModelPurgeServices;

    /**
     * @var PurgeServiceInterface[]
     */
    private $writeModelPurgeServices;

    /**
     * PurgeServiceManager constructor.
     */
    public function __construct()
    {
        $this->readModelPurgeServices = [];

        $this->writeModelPurgeServices = [];
    }


    public function addReadModelPurgeService(PurgeServiceInterface $purgeService)
    {
        $this->readModelPurgeServices[] = $purgeService;
    }

    /**
     * @return PurgeServiceInterface[]
     */
    public function getReadModelPurgeServices()
    {
        return $this->readModelPurgeServices;
    }


    public function addWriteModelPurgeService(PurgeServiceInterface $purgeService)
    {
        $this->writeModelPurgeServices[] = $purgeService;
    }

    /**
     * @return PurgeServiceInterface[]
     */
    public function getWriteModelPurgeServices()
    {
        return $this->writeModelPurgeServices;
    }
}
