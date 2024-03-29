<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint as Udb3ModelContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint instead where possible.
 */
final class ContactPoint implements Serializable, JsonLdSerializableInterface
{
    /**
     * @var array
     */
    private $phones = [];

    /**
     * @var array
     */
    private $emails = [];

    /**
     * @var array
     */
    private $urls = [];

    public function __construct(array $phones = [], array $emails = [], array $urls = [])
    {
        $this->phones = $phones;
        $this->emails = $emails;
        $this->urls = $urls;
    }

    public function getPhones(): array
    {
        return $this->phones;
    }

    public function getEmails(): array
    {
        return $this->emails;
    }

    public function getUrls(): array
    {
        return $this->urls;
    }

    public function serialize(): array
    {
        return [
          'phone' => $this->phones,
          'email' => $this->emails,
          'url' => $this->urls,
        ];
    }

    public static function deserialize(array $data): ContactPoint
    {
        return new self($data['phone'], $data['email'], $data['url']);
    }

    public function toJsonLd(): array
    {
        // Serialized version is the same.
        return $this->serialize();
    }

    public function sameAs(ContactPoint $otherContactPoint): bool
    {
        return $this->toJsonLd() === $otherContactPoint->toJsonLd();
    }

    public static function fromUdb3ModelContactPoint(Udb3ModelContactPoint $contactPoint): ContactPoint
    {
        $phones = array_map(
            function (TelephoneNumber $phone) {
                return $phone->toString();
            },
            $contactPoint->getTelephoneNumbers()->toArray()
        );

        $emails = array_map(
            function (EmailAddress $emailAddress) {
                return $emailAddress->toString();
            },
            $contactPoint->getEmailAddresses()->toArray()
        );

        $urls = array_map(
            function (Url $url) {
                return $url->toString();
            },
            $contactPoint->getUrls()->toArray()
        );

        return new self($phones, $emails, $urls);
    }
}
