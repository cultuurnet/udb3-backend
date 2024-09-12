<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumbers;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use PHPUnit\Framework\TestCase;

class ContactPointTest extends TestCase
{
    private array $phones;

    private array $emails;

    private array $urls;

    private ContactPoint $contactPoint;

    protected function setUp(): void
    {
        $this->phones = ['012 34 56 78', '987 65 43 21'];

        $this->emails = ['user1@company.com', 'user2@company.com'];

        $this->urls = ['http//www.company.be', 'http//www.company.com'];

        $this->contactPoint = new ContactPoint(
            $this->phones,
            $this->emails,
            $this->urls
        );
    }

    /**
     * @test
     */
    public function it_stores_phones(): void
    {
        $this->assertEquals($this->phones, $this->contactPoint->getPhones());
    }

    /**
     * @test
     */
    public function it_stores_emails(): void
    {
        $this->assertEquals($this->emails, $this->contactPoint->getEmails());
    }

    /**
     * @test
     */
    public function it_stores_urls(): void
    {
        $this->assertEquals($this->urls, $this->contactPoint->getUrls());
    }

    /**
     * @test
     */
    public function it_can_compare(): void
    {
        $sameContactPoint = new ContactPoint(
            $this->phones,
            $this->emails,
            $this->urls
        );

        $differentOrderContactPoint = new ContactPoint(
            $this->phones,
            $this->emails,
            ['http//www.company.com', 'http//www.company.be']
        );

        $this->assertTrue($this->contactPoint->sameAs($sameContactPoint));
        $this->assertFalse($this->contactPoint->sameAs($differentOrderContactPoint));
    }

    /**
     * @test
     */
    public function it_should_be_creatable_from_an_udb3_model_contact_point(): void
    {
        $udb3ModelContactPoint = new \CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint(
            new TelephoneNumbers(
                new TelephoneNumber('044/556677'),
                new TelephoneNumber('011/223344')
            ),
            new EmailAddresses(
                new EmailAddress('foo@publiq.be'),
                new EmailAddress('bar@publiq.be')
            ),
            new Urls(
                new Url('https://www.publiq.be'),
                new Url('https://www.uitdatabank.be')
            )
        );

        $expected = new ContactPoint(
            ['044/556677', '011/223344'],
            ['foo@publiq.be', 'bar@publiq.be'],
            ['https://www.publiq.be', 'https://www.uitdatabank.be']
        );
        $actual = ContactPoint::fromUdb3ModelContactPoint($udb3ModelContactPoint);

        $this->assertEquals($expected, $actual);
    }
}
