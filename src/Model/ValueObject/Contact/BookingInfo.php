<?php

namespace CultuurNet\UDB3\Model\ValueObject\Contact;

use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLink;

class BookingInfo
{
    /**
     * @var WebsiteLink|null
     */
    private $website;

    /**
     * @var TelephoneNumber|null
     */
    private $telephoneNumber;

    /**
     * @var EmailAddress|null
     */
    private $emailAddress;

    /**
     * @var BookingAvailability|null
     */
    private $availability;


    public function __construct(
        WebsiteLink $website = null,
        TelephoneNumber $telephoneNumber = null,
        EmailAddress $emailAddress = null,
        BookingAvailability $availability = null
    ) {
        $this->website = $website;
        $this->telephoneNumber = $telephoneNumber;
        $this->emailAddress = $emailAddress;
        $this->availability = $availability;
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

    public function getAvailability(): ?BookingAvailability
    {
        return $this->availability;
    }

    public function withAvailability(BookingAvailability $availability): self
    {
        $c = clone $this;
        $c->availability = $availability;
        return $c;
    }

    public function withoutAvailability(): self
    {
        $c = clone $this;
        $c->availability = null;
        return $c;
    }

    public function isEmpty(): bool
    {
        return is_null($this->website) && is_null($this->telephoneNumber) && is_null($this->emailAddress) &&
            is_null($this->availability);
    }
}
