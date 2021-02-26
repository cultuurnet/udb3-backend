<?php

declare(strict_types=1);

/**
 * @file
 * Contains \namespace CultuurNet\UDB3\Organizer\Events\OrganizerCreated.
 */

namespace CultuurNet\UDB3\Organizer\Events;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Title;

final class OrganizerCreated extends OrganizerEvent
{
    /**
     * @var Title
     */
    public $title;

    /**
     * @var Address[]
     */
    public $addresses;

    /**
     * @var string[]
     */
    public $phones;

    /**
     * @var string[]
     */
    public $emails;

    /**
     * @var string[]
     */
    public $urls;

    /**
     * @param Address[] $addresses
     * @param string[] $phones
     * @param string[] $emails
     * @param string[] $urls
     */
    public function __construct(string $id, Title $title, array $addresses, array $phones, array $emails, array $urls)
    {
        parent::__construct($id);

        $this->guardAddressTypes(...$addresses);

        $this->title = $title;
        $this->addresses = $addresses;
        $this->phones = $phones;
        $this->emails = $emails;
        $this->urls = $urls;
    }

    private function guardAddressTypes(Address ...$addresses): void
    {
    }

    public function getTitle(): Title
    {
        return $this->title;
    }

    /**
     * @return Address[]
     */
    public function getAddresses(): array
    {
        return $this->addresses;
    }

    /**
     * @return string[]
     */
    public function getPhones(): array
    {
        return $this->phones;
    }

    /**
     * @return string[]
     */
    public function getEmails(): array
    {
        return $this->emails;
    }

    /**
     * @return string[]
     */
    public function getUrls(): array
    {
        return $this->urls;
    }

    public function serialize(): array
    {
        $addresses = [];
        foreach ($this->getAddresses() as $address) {
            $addresses[] = $address->serialize();
        }

        return parent::serialize() + [
          'title' => (string) $this->getTitle(),
          'addresses' => $addresses,
          'phones' => $this->getPhones(),
          'emails' => $this->getEmails(),
          'urls' => $this->getUrls(),
        ];
    }

    public static function deserialize(array $data): OrganizerCreated
    {
        $addresses = [];
        foreach ($data['addresses'] as $address) {
            $addresses[] = Address::deserialize($address);
        }

        return new static(
            $data['organizer_id'],
            new Title($data['title']),
            $addresses,
            $data['phones'],
            $data['emails'],
            $data['urls']
        );
    }
}
