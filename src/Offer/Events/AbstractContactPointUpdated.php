<?php

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\ContactPoint;

abstract class AbstractContactPointUpdated extends AbstractEvent
{
    /**
     * @var ContactPoint
     */
    protected $contactPoint;

    final public function __construct(string $id, ContactPoint $contactPoint)
    {
        parent::__construct($id);
        $this->contactPoint = $contactPoint;
    }

    public function getContactPoint(): ContactPoint
    {
        return $this->contactPoint;
    }

    public function serialize(): array
    {
        return parent::serialize() + array(
            'contactPoint' => $this->contactPoint->serialize(),
        );
    }

    public static function deserialize(array $data): AbstractContactPointUpdated
    {
        return new static($data['item_id'], ContactPoint::deserialize($data['contactPoint']));
    }
}
