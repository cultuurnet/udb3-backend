<?php

namespace CultuurNet\UDB3\Model\ValueObject\Contact;

use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;

class ContactPoint
{
    /**
     * @var TelephoneNumbers
     */
    private $telephoneNumbers;

    /**
     * @var EmailAddresses
     */
    private $emailAddresses;

    /**
     * @var Urls
     */
    private $urls;

    /**
     * @param TelephoneNumbers $telephoneNumbers
     * @param EmailAddresses $emailAddresses
     * @param Urls $urls
     */
    public function __construct(
        TelephoneNumbers $telephoneNumbers = null,
        EmailAddresses $emailAddresses = null,
        Urls $urls = null
    ) {
        $this->telephoneNumbers = $telephoneNumbers ? $telephoneNumbers : new TelephoneNumbers();
        $this->emailAddresses = $emailAddresses ? $emailAddresses : new EmailAddresses();
        $this->urls = $urls ? $urls : new Urls();
    }

    /**
     * @return TelephoneNumbers
     */
    public function getTelephoneNumbers()
    {
        return $this->telephoneNumbers;
    }

    /**
     * @return ContactPoint
     */
    public function withTelephoneNumbers(TelephoneNumbers $telephoneNumbers)
    {
        $c = clone $this;
        $c->telephoneNumbers = $telephoneNumbers;
        return $c;
    }

    /**
     * @return EmailAddresses
     */
    public function getEmailAddresses()
    {
        return $this->emailAddresses;
    }

    /**
     * @return ContactPoint
     */
    public function withEmailAddresses(EmailAddresses $emailAddresses)
    {
        $c = clone $this;
        $c->emailAddresses = $emailAddresses;
        return $c;
    }

    /**
     * @return Urls
     */
    public function getUrls()
    {
        return $this->urls;
    }

    /**
     * @return ContactPoint
     */
    public function withUrls(Urls $urls)
    {
        $c = clone $this;
        $c->urls = $urls;
        return $c;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return $this->telephoneNumbers->getLength() === 0 && $this->emailAddresses->getLength() === 0 &&
            $this->urls->getLength() === 0;
    }
}
