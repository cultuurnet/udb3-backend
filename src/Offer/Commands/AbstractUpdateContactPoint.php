<?php

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
     * @param ContactPoint $contactPoint
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
