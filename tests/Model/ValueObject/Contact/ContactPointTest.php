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
    private TelephoneNumbers $telephoneNumbers;

    private EmailAddresses $emailAddresses;

    private Urls $urls;

    private ContactPoint $contactPoint;

    public function setUp(): void
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
    public function it_should_be_creatable_without_any_parameters(): void
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
    public function it_should_throw_an_exception_when_parameters_are_string_arrays(): void
    {
        $this->expectException(\TypeError::class);
        new ContactPoint(
            ['02/551 18 70'],
            ['info@publiq.be', 'vragen@publiq.be'],
            ['https://www.publiq.be']
        );
    }

    /**
     * @test
     */
    public function it_should_return_the_injected_contact_methods(): void
    {
        $this->assertEquals($this->telephoneNumbers, $this->contactPoint->getTelephoneNumbers());
        $this->assertEquals($this->emailAddresses, $this->contactPoint->getEmailAddresses());
        $this->assertEquals($this->urls, $this->contactPoint->getUrls());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_updated_telephone_numbers(): void
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
    public function it_should_return_a_copy_with_updated_email_addresses(): void
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
    public function it_should_return_a_copy_with_updated_urls(): void
    {
        $updatedUrls = $this->urls
            ->with(new Url('http://acme.com'));

        $updatedContactPoint = $this->contactPoint->withUrls($updatedUrls);

        $this->assertNotEquals($this->urls, $updatedUrls);
        $this->assertNotEquals($this->contactPoint, $updatedContactPoint);

        $this->assertEquals($this->urls, $this->contactPoint->getUrls());
        $this->assertEquals($updatedUrls, $updatedContactPoint->getUrls());
    }

    /**
     * @test
     * @dataProvider contactPointDataProvider
     */
    public function it_can_compare(ContactPoint $contactPoint, ContactPoint $otherContactPoint, bool $equal): void
    {
        $this->assertEquals(
            $equal,
            $contactPoint->sameAs($otherContactPoint)
        );
    }

    public function contactPointDataProvider(): array
    {
        return [
            'same contact points' => [
                new ContactPoint(
                    new TelephoneNumbers(
                        new TelephoneNumber('016 10 20 30'),
                        new TelephoneNumber('016 12 34 56')
                    ),
                    new EmailAddresses(
                        new EmailAddress('info@publiq.be'),
                        new EmailAddress('info@publiq.com')
                    ),
                    new Urls(
                        new Url('https://www.publiq.be'),
                        new Url('https://www.publiq.com')
                    )
                ),
                new ContactPoint(
                    new TelephoneNumbers(
                        new TelephoneNumber('016 10 20 30'),
                        new TelephoneNumber('016 12 34 56')
                    ),
                    new EmailAddresses(
                        new EmailAddress('info@publiq.be'),
                        new EmailAddress('info@publiq.com')
                    ),
                    new Urls(
                        new Url('https://www.publiq.be'),
                        new Url('https://www.publiq.com')
                    )
                ),
                true,
            ],
            'same empty contact points' => [
                new ContactPoint(),
                new ContactPoint(),
                true,
            ],
            'different order contact points' => [
                new ContactPoint(
                    new TelephoneNumbers(
                        new TelephoneNumber('016 10 20 30'),
                        new TelephoneNumber('016 12 34 56')
                    )
                ),
                new ContactPoint(
                    new TelephoneNumbers(
                        new TelephoneNumber('016 12 34 56'),
                        new TelephoneNumber('016 10 20 30')
                    )
                ),
                false,
            ],
            'different values contact points' => [
                new ContactPoint(
                    new TelephoneNumbers(
                        new TelephoneNumber('016 10 20 30')
                    )
                ),
                new ContactPoint(
                    new TelephoneNumbers(
                        new TelephoneNumber('016 10 20 40')
                    )
                ),
                false,
            ],
        ];
    }
}
