<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Contact\ContactPointDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Contact\ContactPointNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;

abstract class AbstractContactPointUpdated extends AbstractEvent
{
    protected ContactPoint $contactPoint;

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
        return parent::serialize() + [
            'contactPoint' => (new ContactPointNormalizer())->normalize($this->contactPoint),
        ];
    }

    public static function deserialize(array $data): AbstractContactPointUpdated
    {
        return new static(
            $data['item_id'],
            (new ContactPointDenormalizer())->denormalize($data['contactPoint'], ContactPoint::class)
        );
    }
}
