<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Web\TranslatedWebsiteLabelDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Web\TranslatedWebsiteLabelNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo as Udb3ModelBookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\TranslatedWebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLink;
use DateTimeImmutable;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo instead where possible.
 */
final class BookingInfo implements JsonLdSerializableInterface
{
    private ?TelephoneNumber $phone;

    private ?EmailAddress $email;

    private ?WebsiteLink $website;

    private ?DateTimeImmutable $availabilityStarts;

    private ?DateTimeImmutable $availabilityEnds;

    public function __construct(
        ?WebsiteLink $website = null,
        ?TelephoneNumber $phone = null,
        ?EmailAddress $email = null,
        ?DateTimeImmutable $availabilityStarts = null,
        ?DateTimeImmutable $availabilityEnds = null
    ) {
        $this->website = $website;
        $this->phone = $phone;
        $this->email = $email;
        $this->availabilityStarts = $availabilityStarts;
        $this->availabilityEnds = $availabilityEnds;
    }

    public function getPhone(): ?TelephoneNumber
    {
        return $this->phone;
    }

    public function getEmail(): ?EmailAddress
    {
        return $this->email;
    }

    public function getWebsite(): ?WebsiteLink
    {
        return $this->website;
    }

    public function getAvailabilityStarts(): ?DateTimeImmutable
    {
        return $this->availabilityStarts;
    }

    public function getAvailabilityEnds(): ?DateTimeImmutable
    {
        return $this->availabilityEnds;
    }

    public function serialize(): array
    {
        $serialized = array_filter(
            [
              'phone' => $this->phone ? $this->phone->toString() : null,
              'email' => $this->email ? $this->email->toString() : null,
              'url' => $this->website ? $this->website->getUrl()->toString() : null,
            ]
        );

        if ($this->availabilityStarts) {
            $serialized['availabilityStarts'] = $this->availabilityStarts->format(\DATE_ATOM);
        }

        if ($this->availabilityEnds) {
            $serialized['availabilityEnds'] = $this->availabilityEnds->format(\DATE_ATOM);
        }

        if ($this->website) {
            $serialized['urlLabel'] = (new TranslatedWebsiteLabelNormalizer())->normalize($this->website->getLabel());
        }

        return $serialized;
    }

    public static function deserialize(array $data): BookingInfo
    {
        $defaults = [
            'url' => null,
            'urlLabel' => null,
            'phone' => null,
            'email' => null,
            'availabilityStarts' => null,
            'availabilityEnds' => null,
        ];

        $data = array_merge($defaults, $data);

        $availabilityStarts = null;
        if ($data['availabilityStarts']) {
            $availabilityStarts = DateTimeFactory::fromISO8601($data['availabilityStarts']);
        }

        $availabilityEnds = null;
        if ($data['availabilityEnds']) {
            $availabilityEnds = DateTimeFactory::fromISO8601($data['availabilityEnds']);
        }

        $website = null;
        if (!empty($data['url']) && !empty($data['urlLabel'])) {
            $url = new Url($data['url']);

            /* @var TranslatedWebsiteLabel $label */
            $label = (new TranslatedWebsiteLabelDenormalizer())->denormalize(
                $data['urlLabel'],
                TranslatedWebsiteLabel::class,
            );

            $website = new WebsiteLink($url, $label);
        }

        return new self(
            $website,
            !empty($data['phone']) ? new TelephoneNumber($data['phone']) : null,
            !empty($data['email']) ? new EmailAddress($data['email']) : null,
            $availabilityStarts,
            $availabilityEnds
        );
    }

    public function toJsonLd(): array
    {
        return $this->serialize();
    }

    public function sameAs(BookingInfo $otherBookingInfo): bool
    {
        return $this->toJsonLd() === $otherBookingInfo->toJsonLd();
    }

    public static function fromUdb3ModelBookingInfo(Udb3ModelBookingInfo $udb3ModelBookingInfo): BookingInfo
    {
        $website = null;
        $phone = null;
        $email = null;
        $availabilityStarts = null;
        $availabilityEnds = null;

        if ($udb3ModelWebsite = $udb3ModelBookingInfo->getWebsite()) {
            $website = $udb3ModelWebsite;
        }

        if ($udb3ModelPhone = $udb3ModelBookingInfo->getTelephoneNumber()) {
            $phone = $udb3ModelPhone;
        }

        if ($udb3ModelEmail = $udb3ModelBookingInfo->getEmailAddress()) {
            $email = $udb3ModelEmail;
        }

        if ($udb3ModelAvailability = $udb3ModelBookingInfo->getAvailability()) {
            $availabilityStarts = $udb3ModelAvailability->getFrom();
            $availabilityEnds = $udb3ModelAvailability->getTo();
        }

        return new self(
            $website,
            $phone,
            $email,
            $availabilityStarts,
            $availabilityEnds
        );
    }
}
