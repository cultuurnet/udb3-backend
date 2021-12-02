<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use CultuurNet\UDB3\ContactPoint;

final class ContactPointUpdated extends OrganizerEvent
{
    /**
     * @var string[]
     */
    private array $phones;

    /**
     * @var string[]
     */
    private array $emails;

    /**
     * @var string[]
     */
    private array $urls;

    public function __construct(
        string $organizerId,
        array $phones = [],
        array $emails = [],
        array $urls = []
    ) {
        parent::__construct($organizerId);
        $this->phones = $phones;
        $this->emails = $emails;
        $this->urls = $urls;
    }

    public function getContactPoint(): ContactPoint
    {
        return new ContactPoint($this->phones, $this->emails, $this->urls);
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'phones' => $this->phones,
            'emails' => $this->emails,
            'urls' => $this->urls,
        ];
    }

    public static function deserialize(array $data): ContactPointUpdated
    {
        return new static(
            $data['organizer_id'],
            $data['phones'],
            $data['emails'],
            $data['urls']
        );
    }
}
