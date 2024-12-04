<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Offer;

use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Model\Event\ImmutableEvent;
use CultuurNet\UDB3\Model\Offer\ImmutableOffer;
use CultuurNet\UDB3\Model\Organizer\OrganizerReference;
use CultuurNet\UDB3\Model\Place\PlaceReference;
use CultuurNet\UDB3\Model\ValueObject\Audience\Age;
use CultuurNet\UDB3\Model\ValueObject\Audience\AgeRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
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
use CultuurNet\UDB3\Theme;
use DateTimeImmutable;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

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

    private Udb3ModelToLegacyOfferAdapter $adapter;

    private Udb3ModelToLegacyOfferAdapter $completeAdapter;

    public function setUp(): void
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
                        DateTimeFactory::fromAtom('2018-01-01T10:00:00+01:00'),
                        DateTimeFactory::fromAtom('2018-01-10T10:00:00+01:00')
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
                DateTimeFactory::fromAtom('2040-01-01T10:00:00+01:00')
            );

        $this->adapter = new Udb3ModelToLegacyOfferAdapter($this->offer);
        $this->completeAdapter = new Udb3ModelToLegacyOfferAdapter($this->completeOffer);
    }

    /**
     * @test
     */
    public function it_should_return_a_theme(): void
    {
        $expected = new Theme('0.52.0.0.0', 'Circus');
        $actual = $this->adapter->getTheme();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_null_as_theme_if_there_is_none(): void
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
    public function it_should_return_a_calendar(): void
    {
        $expected = new Calendar(CalendarType::permanent());
        $actual = $this->adapter->getCalendar();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_no_organizer_id_by_default(): void
    {
        $actual = $this->adapter->getOrganizerId();
        $this->assertNull($actual);
    }

    /**
     * @test
     */
    public function it_should_return_an_organizer_id_if_there_is_one(): void
    {
        $expected = 'cc4fa0d1-f86c-42cd-a9c6-995a660ba948';
        $actual = $this->completeAdapter->getOrganizerId();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_default_available_from_if_there_is_none(): void
    {
        $now = new DateTimeImmutable();
        $actual = $this->adapter->getAvailableFrom($now);
        $this->assertEquals($now, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_available_from_if_there_is_one(): void
    {
        $expected = DateTimeFactory::fromAtom('2040-01-01T10:00:00+01:00');
        $actual = $this->completeAdapter->getAvailableFrom(new DateTimeImmutable());
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_default_if_available_from_is_in_the_past(): void
    {
        $now = new DateTimeImmutable();
        $dateInThePast = new DateTimeImmutable('2019-02-14');
        $offer = $this->completeOffer->withAvailableFrom($dateInThePast);
        $adapter = new Udb3ModelToLegacyOfferAdapter($offer);
        $this->assertEquals($now, $adapter->getAvailableFrom($now));
    }

    /**
     * @test
     */
    public function it_should_return_the_title_translations(): void
    {
        $expected = [
            'fr' => new Title('Titre example'),
            'en' => new Title('Example title'),
        ];
        $actual = $this->adapter->getTitleTranslations();
        $this->assertEquals($expected, $actual);
    }
}
