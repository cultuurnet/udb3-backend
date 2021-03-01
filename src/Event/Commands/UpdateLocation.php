<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;

class UpdateLocation extends AbstractCommand
{
    /**
     * @var LocationId
     */
    private $locationId;

    /**
     * UpdateLocation constructor.
     * @param string $itemId
     */
    public function __construct(
        $itemId,
        LocationId $locationId
    ) {
        parent::__construct($itemId);

        $this->locationId = $locationId;
    }

    /**
     * @return LocationId
     */
    public function getLocationId()
    {
        return $this->locationId;
    }
}
