<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;
use Google\Service\SecurityCommandCenter\LoadBalancer;

class UpdateLocation extends AbstractCommand
{
    private LocationId $locationId;

    public function __construct(
        string $itemId,
        LocationId $locationId
    ) {
        parent::__construct($itemId);

        $this->locationId = $locationId;
    }

    public function getLocationId() : LocationId
    {
        return $this->locationId;
    }
}
