<?php

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo as Udb3ModelBookingInfo;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use DateTimeImmutable;

final class BookingInfo implements JsonLdSerializableInterface
{
    /**
     * @var string|null
     */
    protected $phone;

    /**
     * @var string|null
     */
    protected $email;

    /**
     * @var string|null
     */
    protected $url;

    /**
     * @var MultilingualString|null
     */
    protected $urlLabel;

    /**
     * @var DateTimeImmutable|null
     */
    protected $availabilityStarts;

    /**
     * @var DateTimeImmutable|null
     */
    protected $availabilityEnds;

    public function __construct(
        ?string $url = null,
        ?MultilingualString $urlLabel = null,
        ?string $phone = null,
        ?string $email = null,
        ?DateTimeImmutable $availabilityStarts = null,
        ?DateTimeImmutable $availabilityEnds = null
    ) {
        // Workaround to maintain compatibility with older BookingInfo data.
        // Empty BookingInfo properties used to be stored as empty strings in the past.
        // Convert those to null in case they are injected via the constructor (via BookingInfo::deserialize()).
        // API clients are also allowed to send empty strings for BookingInfo properties via EntryAPI3, which should
        // also be treated as null.
        $url = $this->castEmptyStringToNull($url);
        $phone = $this->castEmptyStringToNull($phone);
        $email = $this->castEmptyStringToNull($email);

        $this->url = $url;
        $this->urlLabel = $urlLabel;
        $this->phone = $phone;
        $this->email = $email;
        $this->availabilityStarts = $availabilityStarts;
        $this->availabilityEnds = $availabilityEnds;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getUrlLabel(): ?MultilingualString
    {
        return $this->urlLabel;
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
              'phone' => $this->phone,
              'email' => $this->email,
              'url' => $this->url,
            ]
        );

        if ($this->availabilityStarts) {
            $serialized['availabilityStarts'] = $this->availabilityStarts->format(\DATE_ATOM);
        }

        if ($this->availabilityEnds) {
            $serialized['availabilityEnds'] = $this->availabilityEnds->format(\DATE_ATOM);
        }

        if ($this->urlLabel) {
            $serialized['urlLabel'] = $this->urlLabel->serialize();
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
            $availabilityStarts = DateTimeImmutable::createFromFormat(\DATE_ATOM, $data['availabilityStarts']);
        }

        $availabilityEnds = null;
        if ($data['availabilityEnds']) {
            $availabilityEnds = DateTimeImmutable::createFromFormat(\DATE_ATOM, $data['availabilityEnds']);
        }

        $urlLabel = null;
        if ($data['urlLabel']) {
            $urlLabel = MultilingualString::deserialize($data['urlLabel']);
        }

        return new self(
            $data['url'],
            $urlLabel,
            $data['phone'],
            $data['email'],
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
        $url = null;
        $urlLabel = null;
        $phone = null;
        $email = null;
        $availabilityStarts = null;
        $availabilityEnds = null;

        if ($udb3ModelWebsite = $udb3ModelBookingInfo->getWebsite()) {
            $url = $udb3ModelWebsite->getUrl()->toString();
            $urlLabel = MultilingualString::fromUdb3ModelTranslatedValueObject($udb3ModelWebsite->getLabel());
        }

        if ($udb3ModelPhone = $udb3ModelBookingInfo->getTelephoneNumber()) {
            $phone = $udb3ModelPhone->toString();
        }

        if ($udb3ModelEmail = $udb3ModelBookingInfo->getEmailAddress()) {
            $email = $udb3ModelEmail->toString();
        }

        if ($udb3ModelAvailability = $udb3ModelBookingInfo->getAvailability()) {
            $availabilityStarts = $udb3ModelAvailability->getFrom();
            $availabilityEnds = $udb3ModelAvailability->getTo();
        }

        return new self(
            $url,
            $urlLabel,
            $phone,
            $email,
            $availabilityStarts,
            $availabilityEnds
        );
    }

    private function castEmptyStringToNull(?string $string = null): ?string
    {
        return is_string($string) && $string === '' ? null : $string;
    }
}
