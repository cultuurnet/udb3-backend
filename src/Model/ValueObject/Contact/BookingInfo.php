<?php

namespace CultuurNet\UDB3\Model\ValueObject\Contact;

use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLink;

class BookingInfo
{
    /**
     * @var WebsiteLink
     */
    private $website;

    /**
     * @var TelephoneNumber
     */
    private $telephoneNumber;

    /**
     * @var EmailAddress
     */
    private $emailAddress;

    /**
     * @var BookingAvailability
     */
    private $availability;

    /**
     * @param WebsiteLink|null $website
     * @param TelephoneNumber|null $telephoneNumber
     * @param EmailAddress|null $emailAddress
     * @param BookingAvailability|null $availability
     */
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

    /**
     * @return WebsiteLink|null
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param WebsiteLink $website
     * @return BookingInfo
     */
    public function withWebsite(WebsiteLink $website)
    {
        $c = clone $this;
        $c->website = $website;
        return $c;
    }

    /**
     * @return BookingInfo
     */
    public function withoutWebsite()
    {
        $c = clone $this;
        $c->website = null;
        return $c;
    }

    /**
     * @return TelephoneNumber|null
     */
    public function getTelephoneNumber()
    {
        return $this->telephoneNumber;
    }

    /**
     * @param TelephoneNumber $telephoneNumber
     * @return BookingInfo
     */
    public function withTelephoneNumber(TelephoneNumber $telephoneNumber)
    {
        $c = clone $this;
        $c->telephoneNumber = $telephoneNumber;
        return $c;
    }

    /**
     * @return BookingInfo
     */
    public function withoutTelephoneNumber()
    {
        $c = clone $this;
        $c->telephoneNumber = null;
        return $c;
    }

    /**
     * @return EmailAddress|null
     */
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }

    /**
     * @param EmailAddress $emailAddress
     * @return BookingInfo
     */
    public function withEmailAddress(EmailAddress $emailAddress)
    {
        $c = clone $this;
        $c->emailAddress = $emailAddress;
        return $c;
    }

    /**
     * @return BookingInfo
     */
    public function withoutEmailAddress()
    {
        $c = clone $this;
        $c->emailAddress = null;
        return $c;
    }

    /**
     * @return BookingAvailability|null
     */
    public function getAvailability()
    {
        return $this->availability;
    }

    /**
     * @param BookingAvailability $availability
     * @return BookingInfo
     */
    public function withAvailability(BookingAvailability $availability)
    {
        $c = clone $this;
        $c->availability = $availability;
        return $c;
    }

    /**
     * @return BookingInfo
     */
    public function withoutAvailability()
    {
        $c = clone $this;
        $c->availability = null;
        return $c;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return is_null($this->website) && is_null($this->telephoneNumber) && is_null($this->emailAddress) &&
            is_null($this->availability);
    }
}
