<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Web\TranslatedWebsiteLabelDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Web\TranslatedWebsiteLabelNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo as Udb3ModelBookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\TranslatedWebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLink;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo instead where possible.
 */
final class BookingInfo implements JsonLdSerializableInterface
{
    private ?TelephoneNumber $phone;

    private ?EmailAddress $email;

    private ?WebsiteLink $website;

    private ?BookingAvailability $bookingAvailability;

    public function __construct(
        ?WebsiteLink $website = null,
        ?TelephoneNumber $phone = null,
        ?EmailAddress $email = null,
        ?BookingAvailability $bookingAvailability = null
    ) {
        $this->website = $website;
        $this->phone = $phone;
        $this->email = $email;
        $this->bookingAvailability = $bookingAvailability;
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

    public function getAvailability(): ?BookingAvailability
    {
        return $this->bookingAvailability;
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

        if ($this->bookingAvailability && $this->bookingAvailability->getFrom()) {
            $serialized['availabilityStarts'] = $this->bookingAvailability->getFrom()->format(\DATE_ATOM);
        }

        if ($this->bookingAvailability && $this->bookingAvailability->getTo()) {
            $serialized['availabilityEnds'] = $this->bookingAvailability->getTo()->format(\DATE_ATOM);
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
            self::createBookingAvailability($data['availabilityStarts'], $data['availabilityEnds'])
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
        return new self(
            $udb3ModelBookingInfo->getWebsite(),
            $udb3ModelBookingInfo->getTelephoneNumber(),
            $udb3ModelBookingInfo->getEmailAddress(),
            $udb3ModelBookingInfo->getAvailability()
        );
    }

    private static function createBookingAvailability(?string $from, ?string $to): ?BookingAvailability
    {
        if ($from && $to) {
            return BookingAvailability::fromTo(DateTimeFactory::fromISO8601($from), DateTimeFactory::fromISO8601($to));
        }

        if ($from) {
            return BookingAvailability::from(DateTimeFactory::fromISO8601($from));
        }

        if ($to) {
            return BookingAvailability::to(DateTimeFactory::fromISO8601($to));
        }

        return null;
    }
}
