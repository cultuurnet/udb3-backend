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
    public function __construct(array $purgeServices)
    {
        $this->purgeServices = $purgeServices;
    }

    /**
     * @return PurgeServiceInterface[]
     */
    public function getPurgeServices()
    {
        return $this->purgeServices;
    }
}
