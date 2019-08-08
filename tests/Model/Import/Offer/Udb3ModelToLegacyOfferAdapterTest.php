<?php

namespace CultuurNet\UDB3\Model\Import\Offer;

use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Model\Event\ImmutableEvent;
use CultuurNet\UDB3\Model\Offer\ImmutableOffer;
use CultuurNet\UDB3\Model\Organizer\OrganizerReference;
use CultuurNet\UDB3\Model\Place\PlaceReference;
use CultuurNet\UDB3\Model\ValueObject\Audience\Age;
use CultuurNet\UDB3\Model\ValueObject\Audience\AgeRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumbers;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariff;
use CultuurNet\UDB3\Model\ValueObject\Price\TariffName;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariffs;
use CultuurNet\UDB3\Model\ValueObject\Price\TranslatedTariffName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedDescription;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\TranslatedWebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLink;
use CultuurNet\UDB3\PriceInfo\BasePrice;
use CultuurNet\UDB3\PriceInfo\Price;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class Udb3ModelToLegacyOfferAdapterTest extends TestCase
{
    /**
     * @var ImmutableOffer
     */
    private $offer;

    /**
     * @var ImmutableOffer
     */
    private $completeOffer;

    /**
     * @var Udb3ModelToLegacyOfferAdapter
     */
    private $adapter;

    /**
     * @var Udb3ModelToLegacyOfferAdapter
     */
    private $completeAdapter;

    public function setUp()
    {
        $this->offer = new ImmutableEvent(
            new UUID('91060c19-a860-4a47-8591-8a779bfa520a'),
            new Language('nl'),
            (new TranslatedTitle(new Language('nl'), new Title('Voorbeeld titel')))
                ->withTranslation(new Language('fr'), new Title('Titre example'))
                ->withTranslation(new Language('en'), new Title('Example title')),
            new PermanentCalendar(new OpeningHours()),
            PlaceReference::createWithPlaceId(
                new UUID('6ba87a6b-efea-4467-9e87-458d145384d9')
            ),
            new Categories(
                new Category(
                    new CategoryID('0.6.0.0.0'),
                    new CategoryLabel('Beurs'),
                    new CategoryDomain('eventtype')
                ),
                new Category(
                    new CategoryID('0.52.0.0.0'),
                    new CategoryLabel('Circus'),
                    new CategoryDomain('theme')
                )
            )
        );

        $this->completeOffer = $this->offer
            ->withDescription(
                (new TranslatedDescription(
                    new Language('nl'),
                    new Description('Voorbeeld beschrijving')
                ))->withTranslation(new Language('en'), new Description('Example description'))
            )
            ->withOrganizerReference(
                OrganizerReference::createWithOrganizerId(new UUID('cc4fa0d1-f86c-42cd-a9c6-995a660ba948'))
            )
            ->withAgeRange(
                new AgeRange(new Age(8), new Age(12))
            )
            ->withPriceInfo(
                new PriceInfo(
                    new Tariff(
                        new TranslatedTariffName(
                            new Language('nl'),
                            new TariffName('Basistarief')
                        ),
                        new Money(1500, new Currency('EUR'))
                    ),
                    new Tariffs(
                        new Tariff(
                            new TranslatedTariffName(
                                new Language('nl'),
                                new TariffName('Senioren')
                            ),
                            new Money(1050, new Currency('EUR'))
                        )
                    )
                )
            )
            ->withBookingInfo(
                new BookingInfo(
                    new WebsiteLink(
                        new Url('https://www.publiq.be'),
                        new TranslatedWebsiteLabel(
                            new Language('nl'),
                            new WebsiteLabel('Publiq')
                        )
                    ),
                    new TelephoneNumber('044/444444'),
                    new EmailAddress('info@publiq.be'),
                    new BookingAvailability(
                        \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T10:00:00+01:00'),
                        \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-10T10:00:00+01:00')
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
            )
            ->withAvailableFrom(
                \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T10:00:00+01:00')
            );

        $this->adapter = new Udb3ModelToLegacyOfferAdapter($this->offer);
        $this->completeAdapter = new Udb3ModelToLegacyOfferAdapter($this->completeOffer);
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
    public function it_should_return_a_type()
    {
        $expected = new EventType('0.6.0.0.0', 'Beurs');
        $actual = $this->adapter->getType();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_a_theme()
    {
        $expected = new Theme('0.52.0.0.0', 'Circus');
        $actual = $this->adapter->getTheme();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_null_as_theme_if_there_is_none()
    {
        $offer = $this->offer->withTerms(
            new Categories(
                new Category(
                    new CategoryID('0.6.0.0.0'),
                    new CategoryLabel('Beurs'),
                    new CategoryDomain('eventtype')
                )
            )
        );
        $adapter = new Udb3ModelToLegacyOfferAdapter($offer);

        $actual = $adapter->getTheme();
        $this->assertNull($actual);
    }

    /**
     * @test
     */
    public function it_should_return_a_calendar()
    {
        $expected = new Calendar(CalendarType::PERMANENT());
        $actual = $this->adapter->getCalendar();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_no_description_by_default()
    {
        $actual = $this->adapter->getDescription();
        $this->assertNull($actual);
    }

    /**
     * @test
     */
    public function it_should_return_a_description_if_there_is_one()
    {
        $expected = new \CultuurNet\UDB3\Description('Voorbeeld beschrijving');
        $actual = $this->completeAdapter->getDescription();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_no_organizer_id_by_default()
    {
        $actual = $this->adapter->getOrganizerId();
        $this->assertNull($actual);
    }

    /**
     * @test
     */
    public function it_should_return_an_organizer_id_if_there_is_one()
    {
        $expected = 'cc4fa0d1-f86c-42cd-a9c6-995a660ba948';
        $actual = $this->completeAdapter->getOrganizerId();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_no_age_range_by_default()
    {
        $actual = $this->adapter->getAgeRange();
        $this->assertNull($actual);
    }

    /**
     * @test
     */
    public function it_should_return_an_age_range_if_there_is_one()
    {
        $expected = new \CultuurNet\UDB3\Offer\AgeRange(
            new \ValueObjects\Person\Age(8),
            new \ValueObjects\Person\Age(12)
        );
        $actual = $this->completeAdapter->getAgeRange();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_no_price_info_by_default()
    {
        $actual = $this->adapter->getPriceInfo();
        $this->assertNull($actual);
    }

    /**
     * @test
     */
    public function it_should_return_price_info_if_there_is_any()
    {
        $expected = new \CultuurNet\UDB3\PriceInfo\PriceInfo(
            new BasePrice(
                new Price(1500),
                \ValueObjects\Money\Currency::fromNative('EUR')
            )
        );
        $expected = $expected->withExtraTariff(
            new \CultuurNet\UDB3\PriceInfo\Tariff(
                new MultilingualString(new \CultuurNet\UDB3\Language('nl'), new StringLiteral('Senioren')),
                new Price(1050),
                \ValueObjects\Money\Currency::fromNative('EUR')
            )
        );
        $actual = $this->completeAdapter->getPriceInfo();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_empty_booking_info_by_default()
    {
        $expected = new \CultuurNet\UDB3\BookingInfo();
        $actual = $this->adapter->getBookingInfo();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_booking_info_if_there_is_any()
    {
        $expected = new \CultuurNet\UDB3\BookingInfo(
            'https://www.publiq.be',
            new MultilingualString(
                new \CultuurNet\UDB3\Language('nl'),
                new StringLiteral('Publiq')
            ),
            '044/444444',
            'info@publiq.be',
            new \DateTimeImmutable('2018-01-01T10:00:00+01:00'),
            new \DateTimeImmutable('2018-01-10T10:00:00+01:00')
        );
        $actual = $this->completeAdapter->getBookingInfo();
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
    public function it_should_return_no_available_from_by_default()
    {
        $actual = $this->adapter->getAvailableFrom();
        $this->assertNull($actual);
    }

    /**
     * @test
     */
    public function it_should_return_available_from_if_there_is_one()
    {
        $expected = \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T10:00:00+01:00');
        $actual = $this->completeAdapter->getAvailableFrom();
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
    public function it_should_return_no_description_translations_by_default()
    {
        $expected = [];
        $actual = $this->adapter->getDescriptionTranslations();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_description_translations_if_there_are_any()
    {
        $expected = [
            'en' => new \CultuurNet\UDB3\Description('Example description'),
        ];
        $actual = $this->completeAdapter->getDescriptionTranslations();
        $this->assertEquals($expected, $actual);
    }
}
