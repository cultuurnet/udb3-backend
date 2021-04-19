<?php

declare(strict_types=1);

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
     * PurgeServiceManager constructor.
     */
    public function __construct()
    {
        $this->readModelPurgeServices = [];
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
}
