<?php

namespace CultuurNet\UDB3\Organizer\Events;

use CultuurNet\UDB3\ContactPoint;

final class ContactPointUpdated extends OrganizerEvent
{
    /**
     * @var ContactPoint
     */
    private $contactPoint;

    public function __construct(
        string $organizerId,
        ContactPoint $contactPoint
    ) {
        parent::__construct($organizerId);
        $this->contactPoint = $contactPoint;
    }

    public function getContactPoint(): ContactPoint
    {
        return $this->contactPoint;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'contactPoint' => $this->contactPoint->serialize(),
        ];
    }

    public static function deserialize(array $data): ContactPointUpdated
    {
        return new static(
            $data['organizer_id'],
            ContactPoint::deserialize($data['contactPoint'])
        );
    }
}
