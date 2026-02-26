<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Contact;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Contact\BookingInfoNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLink;

class BookingInfo
{
    private ?WebsiteLink $website;

    private ?TelephoneNumber $telephoneNumber;

    private ?EmailAddress $emailAddress;

    private ?BookingDateRange $bookingDateRange;

    public function __construct(
        WebsiteLink $website = null,
        TelephoneNumber $telephoneNumber = null,
        EmailAddress $emailAddress = null,
        BookingDateRange $bookingDateRange = null
    ) {
        $this->website = $website;
        $this->telephoneNumber = $telephoneNumber;
        $this->emailAddress = $emailAddress;
        $this->bookingDateRange = $bookingDateRange;
    }

    public function getWebsite(): ?WebsiteLink
    {
        return $this->website;
    }

    public function withWebsite(WebsiteLink $website): self
    {
        $c = clone $this;
        $c->website = $website;
        return $c;
    }

    public function withoutWebsite(): self
    {
        $c = clone $this;
        $c->website = null;
        return $c;
    }

    public function getTelephoneNumber(): ?TelephoneNumber
    {
        return $this->telephoneNumber;
    }

    public function withTelephoneNumber(TelephoneNumber $telephoneNumber): self
    {
        $c = clone $this;
        $c->telephoneNumber = $telephoneNumber;
        return $c;
    }

    public function withoutTelephoneNumber(): self
    {
        $c = clone $this;
        $c->telephoneNumber = null;
        return $c;
    }

    public function getEmailAddress(): ?EmailAddress
    {
        return $this->emailAddress;
    }

    public function withEmailAddress(EmailAddress $emailAddress): self
    {
        $c = clone $this;
        $c->emailAddress = $emailAddress;
        return $c;
    }

    public function withoutEmailAddress(): self
    {
        $c = clone $this;
        $c->emailAddress = null;
        return $c;
    }

    public function getBookingDateRange(): ?BookingDateRange
    {
        return $this->bookingDateRange;
    }

    public function withBookingDateRange(BookingDateRange $bookingDateRange): self
    {
        $c = clone $this;
        $c->bookingDateRange = $bookingDateRange;
        return $c;
    }

    public function withoutBookingDateRange(): self
    {
        $c = clone $this;
        $c->bookingDateRange = null;
        return $c;
    }

    public function isEmpty(): bool
    {
        return is_null($this->website) && is_null($this->telephoneNumber) && is_null($this->emailAddress) &&
            is_null($this->bookingDateRange);
    }

    public function sameAs(BookingInfo $other): bool
    {
        $bookingInfoNormalizer = new BookingInfoNormalizer();

        return $bookingInfoNormalizer->normalize($this) === $bookingInfoNormalizer->normalize($other);
    }
}
