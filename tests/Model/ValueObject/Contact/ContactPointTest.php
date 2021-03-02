<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Contact;

use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use PHPUnit\Framework\TestCase;

class ContactPointTest extends TestCase
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
     * @var ContactPoint
     */
    private $contactPoint;

    public function setUp()
    {
        $this->telephoneNumbers = new TelephoneNumbers(
            new TelephoneNumber('044/444444'),
            new TelephoneNumber('800-123-456')
        );

        $this->emailAddresses = new EmailAddresses(
            new EmailAddress('test@foo.com'),
            new EmailAddress('acme@foo.com'),
            new EmailAddress('acme@lorem.com')
        );

        $this->urls = new Urls(
            new Url('https://google.com')
        );

        $this->contactPoint = new ContactPoint(
            $this->telephoneNumbers,
            $this->emailAddresses,
            $this->urls
        );
    }

    /**
     * @test
     */
    public function it_should_be_creatable_without_any_parameters()
    {
        $contactPoint = new ContactPoint();

        $this->assertEquals(new TelephoneNumbers(), $contactPoint->getTelephoneNumbers());
        $this->assertEquals(new EmailAddresses(), $contactPoint->getEmailAddresses());
        $this->assertEquals(new Urls(), $contactPoint->getUrls());
        $this->assertTrue($contactPoint->isEmpty());
    }

    /**
     * @test
     */
    public function it_should_return_the_injected_contact_methods()
    {
        $this->assertEquals($this->telephoneNumbers, $this->contactPoint->getTelephoneNumbers());
        $this->assertEquals($this->emailAddresses, $this->contactPoint->getEmailAddresses());
        $this->assertEquals($this->urls, $this->contactPoint->getUrls());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_updated_telephone_numbers()
    {
        $updatedTelephoneNumbers = $this->telephoneNumbers
            ->with(new TelephoneNumber('046/464646'));

        $updatedContactPoint = $this->contactPoint->withTelephoneNumbers($updatedTelephoneNumbers);

        $this->assertNotEquals($this->telephoneNumbers, $updatedTelephoneNumbers);
        $this->assertNotEquals($this->contactPoint, $updatedContactPoint);

        $this->assertEquals($this->telephoneNumbers, $this->contactPoint->getTelephoneNumbers());
        $this->assertEquals($updatedTelephoneNumbers, $updatedContactPoint->getTelephoneNumbers());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_updated_email_addresses()
    {
        $updatedEmailAddresses = $this->emailAddresses
            ->with(new EmailAddress('test2@foo.com'));

        $updatedContactPoint = $this->contactPoint->withEmailAddresses($updatedEmailAddresses);

        $this->assertNotEquals($this->emailAddresses, $updatedEmailAddresses);
        $this->assertNotEquals($this->contactPoint, $updatedContactPoint);

        $this->assertEquals($this->emailAddresses, $this->contactPoint->getEmailAddresses());
        $this->assertEquals($updatedEmailAddresses, $updatedContactPoint->getEmailAddresses());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_updated_urls()
    {
        $updatedUrls = $this->urls
            ->with(new Url('http://acme.com'));

        $updatedContactPoint = $this->contactPoint->withUrls($updatedUrls);

        $this->assertNotEquals($this->urls, $updatedUrls);
        $this->assertNotEquals($this->contactPoint, $updatedContactPoint);

        $this->assertEquals($this->urls, $this->contactPoint->getUrls());
        $this->assertEquals($updatedUrls, $updatedContactPoint->getUrls());
    }
}
