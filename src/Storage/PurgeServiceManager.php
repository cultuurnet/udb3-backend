<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Storage;

class PurgeServiceManager
{
    /**
     * @var PurgeServiceInterface[]
     */
    private $purgeServices;

    public function __construct(array $purgeServices)
    {
        $this->purgeServices = $purgeServices;
    }

    /**
     * @return PurgeServiceInterface[]
     */
    public function getPurgeServices(): array
    {
        return $this->purgeServices;
    }
}
