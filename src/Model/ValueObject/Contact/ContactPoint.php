<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Contact;

use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;

class ContactPoint
{
    private TelephoneNumbers $telephoneNumbers;

    private EmailAddresses $emailAddresses;

    private Urls $urls;

    public function __construct(
        TelephoneNumbers $telephoneNumbers = null,
        EmailAddresses $emailAddresses = null,
        Urls $urls = null
    ) {
        $this->telephoneNumbers = $telephoneNumbers ?: new TelephoneNumbers();
        $this->emailAddresses = $emailAddresses ?: new EmailAddresses();
        $this->urls = $urls ?: new Urls();
    }

    public function getTelephoneNumbers(): TelephoneNumbers
    {
        return $this->telephoneNumbers;
    }

    public function withTelephoneNumbers(TelephoneNumbers $telephoneNumbers): ContactPoint
    {
        $c = clone $this;
        $c->telephoneNumbers = $telephoneNumbers;
        return $c;
    }

    public function getEmailAddresses(): EmailAddresses
    {
        return $this->emailAddresses;
    }

    public function withEmailAddresses(EmailAddresses $emailAddresses): ContactPoint
    {
        $c = clone $this;
        $c->emailAddresses = $emailAddresses;
        return $c;
    }

    public function getUrls(): Urls
    {
        return $this->urls;
    }

    public function withUrls(Urls $urls): ContactPoint
    {
        $c = clone $this;
        $c->urls = $urls;
        return $c;
    }

    public function isEmpty(): bool
    {
        return $this->telephoneNumbers->getLength() === 0 && $this->emailAddresses->getLength() === 0 &&
            $this->urls->getLength() === 0;
    }

    public function sameAs(ContactPoint $other): bool
    {
        return $this->telephoneNumbers->sameAs($other->getTelephoneNumbers()) &&
            $this->emailAddresses->sameAs($other->getEmailAddresses()) &&
            $this->urls->sameAs($other->getUrls());
    }
}
