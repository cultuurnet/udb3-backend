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
        $this->readModelPurgeServices = array();

        $this->writeModelPurgeServices = array();
    }

    /**
     * @param PurgeServiceInterface $purgeService
     */
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

    /**
     * @param PurgeServiceInterface $purgeService
     */
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
