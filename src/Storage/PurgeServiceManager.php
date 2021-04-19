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
    private $purgeServices;

    /**
     * PurgeServiceManager constructor.
     */
    public function __construct()
    {
        $this->purgeServices = [];
    }


    public function addPurgeService(PurgeServiceInterface $purgeService)
    {
        $this->purgeServices[] = $purgeService;
    }

    /**
     * @return PurgeServiceInterface[]
     */
    public function getPurgeServices()
    {
        return $this->purgeServices;
    }
}
