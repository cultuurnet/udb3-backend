<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\ContactPoint;

abstract class AbstractUpdateContactPoint extends AbstractCommand
{
    /**
     * The contactPoint entry
     * @var ContactPoint
     */
    protected $contactPoint;

    /**
     * @param string $itemId
     */
    public function __construct($itemId, ContactPoint $contactPoint)
    {
        parent::__construct($itemId);
        $this->contactPoint = $contactPoint;
    }

    /**
     * @return ContactPoint
     */
    public function getContactPoint()
    {
        return $this->contactPoint;
    }
}
