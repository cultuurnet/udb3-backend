<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Offer;

use CultuurNet\UDB3\Model\Import\Organizer\Udb3ModelToLegacyOrganizerAdapter;
use CultuurNet\UDB3\Model\Organizer\ImmutableOrganizer;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumbers;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use PHPUnit\Framework\TestCase;
use ValueObjects\Geography\Country;

class Udb3ModelToLegacyOrganizerAdapterTest extends TestCase
{
    /**
     * @var ImmutableOrganizer
     */
    private $organizer;

    /**
     * @var ImmutableOrganizer
     */
    private $completeOrganizer;

    /**
     * @var Udb3ModelToLegacyOrganizerAdapter
     */
    private $adapter;

    /**
     * @var Udb3ModelToLegacyOrganizerAdapter
     */
    private $completeAdapter;

    public function setUp()
    {
        $this->organizer = new ImmutableOrganizer(
            new UUID('91060c19-a860-4a47-8591-8a779bfa520a'),
            new Language('nl'),
            (new TranslatedTitle(new Language('nl'), new Title('Voorbeeld titel')))
                ->withTranslation(new Language('fr'), new Title('Titre example'))
                ->withTranslation(new Language('en'), new Title('Example title')),
            new Url('https://www.publiq.be')
        );

        $this->completeOrganizer = $this->organizer
            ->withAddress(
                (new TranslatedAddress(
                    new Language('nl'),
                    new Address(
                        new Street('Henegouwenkaai 41-43'),
                        new PostalCode('1080'),
                        new Locality('Brussel'),
                        new CountryCode('BE')
                    )
                ))->withTranslation(
                    new Language('fr'),
                    new Address(
                        new Street('Quai du Hainaut 41-43'),
                        new PostalCode('1080'),
                        new Locality('Bruxelles'),
                        new CountryCode('BE')
                    )
                )
            )
            ->withContactPoint(
                new ContactPoint(
                    new TelephoneNumbers(
                        new TelephoneNumber('044/444444'),
                        new TelephoneNumber('055/555555')
                    ),
                    new EmailAddresses(
                        new EmailAddress('foo@publiq.be'),
                        new EmailAddress('bar@publiq.be')
                    ),
                    new Urls(
                        new Url('https://www.publiq.be'),
                        new Url('https://www.uitdatabank.be')
                    )
                )
            );

        $this->adapter = new Udb3ModelToLegacyOrganizerAdapter($this->organizer);
        $this->completeAdapter = new Udb3ModelToLegacyOrganizerAdapter($this->completeOrganizer);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_the_given_organizer_has_no_url()
    {
        $organizer = new ImmutableOrganizer(
            new UUID('91060c19-a860-4a47-8591-8a779bfa520a'),
            new Language('nl'),
            (new TranslatedTitle(new Language('nl'), new Title('Voorbeeld titel')))
                ->withTranslation(new Language('fr'), new Title('Titre example'))
                ->withTranslation(new Language('en'), new Title('Example title'))
        );

        $this->expectException(\InvalidArgumentException::class);

        new Udb3ModelToLegacyOrganizerAdapter($organizer);
    }

    /**
     * @test
     */
    public function it_should_return_an_id()
    {
        $expected = '91060c19-a860-4a47-8591-8a779bfa520a';
        $actual = $this->adapter->getId();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_a_main_language()
    {
        $expected = new \CultuurNet\UDB3\Language('nl');
        $actual = $this->adapter->getMainLanguage();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_a_title()
    {
        $expected = new \CultuurNet\UDB3\Title('Voorbeeld titel');
        $actual = $this->adapter->getTitle();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_a_website()
    {
        $expected = \ValueObjects\Web\Url::fromNative('https://www.publiq.be');
        $actual = $this->adapter->getWebsite();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_an_empty_contact_point_by_default()
    {
        $expected = new \CultuurNet\UDB3\ContactPoint();
        $actual = $this->adapter->getContactPoint();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_a_contact_point_if_there_is_one()
    {
        $expected = new \CultuurNet\UDB3\ContactPoint(
            ['044/444444', '055/555555'],
            ['foo@publiq.be', 'bar@publiq.be'],
            ['https://www.publiq.be', 'https://www.uitdatabank.be']
        );
        $actual = $this->completeAdapter->getContactPoint();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_no_address_by_default()
    {
        $actual = $this->adapter->getAddress();
        $this->assertNull($actual);
    }

    /**
     * @test
     */
    public function it_should_return_an_address_if_there_is_one()
    {
        $expected = new \CultuurNet\UDB3\Address\Address(
            new \CultuurNet\UDB3\Address\Street('Henegouwenkaai 41-43'),
            new \CultuurNet\UDB3\Address\PostalCode('1080'),
            new \CultuurNet\UDB3\Address\Locality('Brussel'),
            new Country(\ValueObjects\Geography\CountryCode::fromNative('BE'))
        );
        $actual = $this->completeAdapter->getAddress();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_the_title_translations()
    {
        $expected = [
            'fr' => new \CultuurNet\UDB3\Title('Titre example'),
            'en' => new \CultuurNet\UDB3\Title('Example title'),
        ];
        $actual = $this->adapter->getTitleTranslations();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_address_translations()
    {
        $expected = [
            'fr' => new \CultuurNet\UDB3\Address\Address(
                new \CultuurNet\UDB3\Address\Street('Quai du Hainaut 41-43'),
                new \CultuurNet\UDB3\Address\PostalCode('1080'),
                new \CultuurNet\UDB3\Address\Locality('Bruxelles'),
                new Country(\ValueObjects\Geography\CountryCode::fromNative('BE'))
            ),
        ];
        $actual = $this->completeAdapter->getAddressTranslations();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_no_address_translations_if_there_are_none()
    {
        $expected = [];
        $actual = $this->adapter->getAddressTranslations();
        $this->assertEquals($expected, $actual);
    }
}
