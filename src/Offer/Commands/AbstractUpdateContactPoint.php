<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;

abstract class AbstractUpdateContactPoint extends AbstractCommand
{
    protected ContactPoint $contactPoint;

    public function __construct(string $itemId, ContactPoint $contactPoint)
    {
        parent::__construct($itemId);
        $this->contactPoint = $contactPoint;
    }

    public function getContactPoint(): ContactPoint
    {
        return $this->contactPoint;
    }
}
