<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Offer;

use Money\UnknownCurrencyException;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Model\Organizer\OrganizerReference;
use CultuurNet\UDB3\Model\ValueObject\Audience\Age;
use CultuurNet\UDB3\Model\ValueObject\Audience\AgeRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SingleSubEventCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumbers;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Image;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Images;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\VideoCollection;
use CultuurNet\UDB3\Model\ValueObject\Moderation\AvailableTo;
use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariff;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariffs;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedDescription;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\TranslatedWebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLink;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class ImmutableOfferTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_initial_properties_and_some_sensible_defaults(): void
    {
        $offer = $this->getOffer();

        $this->assertEquals($this->getId(), $offer->getId());
        $this->assertEquals($this->getMainLanguage(), $offer->getMainLanguage());
        $this->assertEquals($this->getTitle(), $offer->getTitle());
        $this->assertEquals($this->getTerms(), $offer->getTerms());

        $this->assertNull($offer->getDescription());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_title(): void
    {
        $originalTitle = $this->getTitle();
        $updatedTitle = $this->getTitle()
            ->withTranslation(new Language('nl'), new Title('foo UPDATED'))
            ->withTranslation(new Language('en'), new Title('bar'));

        $offer = $this->getOffer();
        $updatedOffer = $offer->withTitle($updatedTitle);

        $this->assertNotEquals($updatedOffer, $offer);
        $this->assertEquals($originalTitle, $offer->getTitle());
        $this->assertEquals($updatedTitle, $updatedOffer->getTitle());
    }

    /**
     * @test
     */
    public function it_should_return_the_injected_calendar(): void
    {
        $calendar = $this->getCalendar();
        $offer = $this->getOffer();

        $this->assertEquals($calendar, $offer->getCalendar());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_calendar(): void
    {
        $calendar = $this->getCalendar();
        $offer = $this->getOffer();

        $updatedCalendar = new PermanentCalendar(new OpeningHours());
        $updatedEvent = $offer->withCalendar($updatedCalendar);

        $this->assertNotEquals($calendar, $updatedCalendar);
        $this->assertEquals($calendar, $offer->getCalendar());
        $this->assertEquals($updatedCalendar, $updatedEvent->getCalendar());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_a_description(): void
    {
        $description = new TranslatedDescription(
            new Language('nl'),
            new Description('lorem')
        );

        $offer = $this->getOffer();
        $updatedOffer = $offer->withDescription($description);

        $this->assertNotEquals($updatedOffer, $offer);
        $this->assertNull($offer->getDescription());
        $this->assertEquals($description, $updatedOffer->getDescription());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_description(): void
    {
        $initialDescription = new TranslatedDescription(
            new Language('nl'),
            new Description('lorem')
        );

        $updatedDescription = $initialDescription
            ->withTranslation(new Language('fr'), new Description('ipsum'));

        $offer = $this->getOffer()->withDescription($initialDescription);
        $updatedOffer = $offer->withDescription($updatedDescription);

        $this->assertNotEquals($updatedOffer, $offer);
        $this->assertEquals($initialDescription, $offer->getDescription());
        $this->assertEquals($updatedDescription, $updatedOffer->getDescription());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_without_description(): void
    {
        $description = new TranslatedDescription(
            new Language('nl'),
            new Description('lorem')
        );

        $offer = $this->getOffer()->withDescription($description);
        $updatedOffer = $offer->withoutDescription();

        $this->assertNotEquals($offer, $updatedOffer);
        $this->assertEquals($description, $offer->getDescription());
        $this->assertNull($updatedOffer->getDescription());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_updated_terms(): void
    {
        $updatedTerms = new Categories(
            new Category(
                new CategoryID('0.50.1.0.0'),
                new CategoryLabel('Concert'),
                new CategoryDomain('eventtype')
            ),
            new Category(
                new CategoryID('0.50.2.0.0'),
                new CategoryLabel('blues'),
                new CategoryDomain('theme')
            )
        );

        $offer = $this->getOffer();
        $updatedOffer = $offer->withTerms($updatedTerms);

        $this->assertNotEquals($offer, $updatedOffer);
        $this->assertEquals($this->getTerms(), $offer->getTerms());
        $this->assertEquals($updatedTerms, $updatedOffer->getTerms());
    }

    /**
     * @test
     */
    public function it_should_return_an_empty_list_of_labels_by_default(): void
    {
        $this->assertEquals(new Labels(), $this->getOffer()->getLabels());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_updated_labels(): void
    {
        $labels = new Labels();
        $updatedLabels = new Labels(
            new Label(
                new LabelName('foo'),
                true
            ),
            new Label(
                new LabelName('bar'),
                false
            )
        );

        $offer = $this->getOffer();
        $updatedOffer = $offer->withLabels($updatedLabels);

        $this->assertNotEquals($offer, $updatedOffer);
        $this->assertEquals($labels, $offer->getLabels());
        $this->assertEquals($updatedLabels, $updatedOffer->getLabels());
    }

    /**
     * @test
     */
    public function it_should_return_no_organizer_reference_by_default(): void
    {
        $this->assertNull($this->getOffer()->getOrganizerReference());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_organizer_reference(): void
    {
        $reference = OrganizerReference::createWithOrganizerId(
            new Uuid('dd5e196a-4afb-449a-bcce-0120d01263b9')
        );

        $offer = $this->getOffer();
        $updatedOffer = $offer->withOrganizerReference($reference);

        $this->assertNotEquals($offer, $updatedOffer);
        $this->assertNull($offer->getOrganizerReference());
        $this->assertEquals($reference, $updatedOffer->getOrganizerReference());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_without_an_organizer_reference(): void
    {
        $reference = OrganizerReference::createWithOrganizerId(
            new Uuid('dd5e196a-4afb-449a-bcce-0120d01263b9')
        );

        $offer = $this->getOffer()->withOrganizerReference($reference);
        $updatedOffer = $offer->withoutOrganizerReference();

        $this->assertNotEquals($offer, $updatedOffer);
        $this->assertEquals($reference, $offer->getOrganizerReference());
        $this->assertNull($updatedOffer->getOrganizerReference());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_age_range(): void
    {
        $ageRange = new AgeRange(new Age(8), new Age(12));

        $offer = $this->getOffer();
        $updatedOffer = $offer->withAgeRange($ageRange);

        $this->assertNotEquals($updatedOffer, $offer);
        $this->assertNull($offer->getAgeRange());
        $this->assertEquals($ageRange, $updatedOffer->getAgeRange());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_age_range(): void
    {
        $initialAgeRange = new AgeRange(new Age(8), new Age(14));
        $updatedAgeRange = new AgeRange(new Age(8), new Age(12));

        $initialOffer = $this->getOffer()->withAgeRange($initialAgeRange);
        $updatedOffer = $initialOffer->withAgeRange($updatedAgeRange);

        $this->assertNotEquals($updatedOffer, $initialOffer);
        $this->assertEquals($initialAgeRange, $initialOffer->getAgeRange());
        $this->assertEquals($updatedAgeRange, $updatedOffer->getAgeRange());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_without_age_range(): void
    {
        $ageRange = new AgeRange(new Age(8), new Age(12));

        $initialOffer = $this->getOffer()->withAgeRange($ageRange);
        $updatedOffer = $initialOffer->withoutAgeRange();

        $this->assertNotEquals($updatedOffer, $initialOffer);
        $this->assertEquals($ageRange, $initialOffer->getAgeRange());
        $this->assertNull($updatedOffer->getAgeRange());
    }

    /**
     * @test
     */
    public function it_should_return_no_price_info_by_default(): void
    {
        $this->assertNull($this->getOffer()->getPriceInfo());
    }

    /**
     * @test
     * @throws UnknownCurrencyException
     */
    public function it_should_return_a_copy_with_updated_price_info(): void
    {
        $priceInfo = new PriceInfo(
            Tariff::createBasePrice(
                new Money(1000, new Currency('EUR'))
            ),
            new Tariffs()
        );

        $offer = $this->getOffer();
        $updatedOffer = $offer->withPriceInfo($priceInfo);

        $this->assertNotEquals($offer, $updatedOffer);
        $this->assertNull($offer->getPriceInfo());
        $this->assertEquals($priceInfo, $updatedOffer->getPriceInfo());
    }

    /**
     * @test
     * @throws UnknownCurrencyException
     */
    public function it_should_return_a_copy_without_price_info(): void
    {
        $priceInfo = new PriceInfo(
            Tariff::createBasePrice(
                new Money(1000, new Currency('EUR'))
            ),
            new Tariffs()
        );

        $offer = $this->getOffer()->withPriceInfo($priceInfo);
        $updatedOffer = $offer->withoutPriceInfo();

        $this->assertNotEquals($offer, $updatedOffer);
        $this->assertEquals($priceInfo, $offer->getPriceInfo());
        $this->assertNull($updatedOffer->getPriceInfo());
    }

    /**
     * @test
     */
    public function it_should_return_empty_booking_info_by_default(): void
    {
        $this->assertTrue($this->getOffer()->getBookingInfo()->isEmpty());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_booking_info(): void
    {
        $offer = $this->getOffer();
        $bookingInfo = $offer->getBookingInfo();

        $updatedBookingInfo = new BookingInfo(
            new WebsiteLink(
                new Url('https://google.com'),
                new TranslatedWebsiteLabel(
                    new Language('nl'),
                    new WebsiteLabel('Google')
                )
            )
        );
        $updatedOffer = $offer->withBookingInfo($updatedBookingInfo);

        $this->assertNotEquals($offer, $updatedOffer);
        $this->assertEquals($bookingInfo, $offer->getBookingInfo());
        $this->assertEquals($updatedBookingInfo, $updatedOffer->getBookingInfo());
    }

    /**
     * @test
     */
    public function it_should_return_an_empty_contact_point_by_default(): void
    {
        $this->assertTrue($this->getOffer()->getContactPoint()->isEmpty());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_contact_point(): void
    {
        $offer = $this->getOffer();
        $contactPoint = $offer->getContactPoint();

        $updatedContactPoint = new ContactPoint(
            new TelephoneNumbers(
                new TelephoneNumber('044/444444')
            )
        );
        $updatedOffer = $offer->withContactPoint($updatedContactPoint);

        $this->assertNotEquals($offer, $updatedOffer);
        $this->assertEquals($contactPoint, $offer->getContactPoint());
        $this->assertEquals($updatedContactPoint, $updatedOffer->getContactPoint());
    }

    /**
     * @test
     */
    public function it_should_return_empty_images_by_default(): void
    {
        $this->assertEquals(new Images(), $this->getOffer()->getImages());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_updated_image(): void
    {
        $image = new Image(
            new Uuid('0bda23b1-3332-4866-b69b-1f1c1d1dbcb4'),
            new Language('nl'),
            new Description('Een afbeelding beschrijving'),
            new CopyrightHolder('Publiq vzw')
        );

        $images = new Images($image);

        $offer = $this->getOffer();
        $updatedOffer = $offer->withImages($images);

        $this->assertNotEquals($offer, $updatedOffer);
        $this->assertEquals(new Images(), $offer->getImages());
        $this->assertEquals($images, $updatedOffer->getImages());
    }

    /**
     * @test
     */
    public function it_has_no_videos_by_default(): void
    {
        $this->assertEquals(new VideoCollection(), $this->getOffer()->getVideos());
    }

    /**
     * @test
     */
    public function it_can_add_videos_to_a_copy(): void
    {
        $videos = new VideoCollection(
            new Video(
                '91c75325-3830-4000-b580-5778b2de4548',
                new Url('https://www.youtube.com/watch?v=123'),
                new Language('nl')
            )
        );

        $offer = $this->getOffer();
        $updatedOffer = $offer->withVideos($videos);

        $this->assertNotEquals($offer, $updatedOffer);
        $this->assertEquals(new VideoCollection(), $offer->getVideos());
        $this->assertEquals($videos, $updatedOffer->getVideos());
    }

    /**
     * @test
     */
    public function it_should_return_a_draft_workflow_status_by_default(): void
    {
        $workflowStatus = $this->getOffer()->getWorkflowStatus();
        $this->assertTrue($workflowStatus->sameAs(WorkflowStatus::DRAFT()));
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_workflow_status(): void
    {
        $offer = $this->getOffer();
        $workflowStatus = $offer->getWorkflowStatus();

        $updatedWorkflowStatus = WorkflowStatus::APPROVED();
        $updatedOffer = $offer->withWorkflowStatus($updatedWorkflowStatus);

        $this->assertNotEquals($offer, $updatedOffer);
        $this->assertEquals($workflowStatus, $offer->getWorkflowStatus());
        $this->assertEquals($updatedWorkflowStatus, $updatedOffer->getWorkflowStatus());
    }

    /**
     * @test
     */
    public function it_should_return_no_available_from_by_default(): void
    {
        $this->assertNull($this->getOffer()->getAvailableFrom());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_available_from(): void
    {
        $availableFrom = DateTimeFactory::fromAtom('2018-01-01T00:00:00+00:00');

        $offer = $this->getOffer();
        $updatedOffer = $offer->withAvailableFrom($availableFrom);

        $this->assertNotEquals($offer, $updatedOffer);
        $this->assertNull($offer->getAvailableFrom());
        $this->assertEquals($availableFrom, $updatedOffer->getAvailableFrom());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_without_available_from(): void
    {
        $availableFrom = DateTimeFactory::fromAtom('2018-01-01T00:00:00+00:00');

        $offer = $this->getOffer()->withAvailableFrom($availableFrom);
        $updatedOffer = $offer->withoutAvailableFrom();

        $this->assertNotEquals($offer, $updatedOffer);
        $this->assertEquals($availableFrom, $offer->getAvailableFrom());
        $this->assertNull($updatedOffer->getAvailableFrom());
    }

    /**
     * @test
     */
    public function it_should_return_the_calendar_end_date_as_available_to(): void
    {
        $offer = $this->getOffer();
        $calendar = $offer->getCalendar();

        if (! $calendar instanceof SingleSubEventCalendar) {
            $this->addWarning(sprintf('Calender expected a %s, received %s', SingleSubEventCalendar::class, get_class($calendar)));

            return;
        }

        $expected = $calendar->getEndDate();
        $actual = $offer->getAvailableTo();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_available_forever_as_available_to(): void
    {
        $calendar = new PermanentCalendar(new OpeningHours());
        $offer = new MockImmutableOffer(
            $this->getId(),
            $this->getMainLanguage(),
            $this->getTitle(),
            $calendar,
            $this->getTerms()
        );

        $expected = AvailableTo::forever();
        $actual = $offer->getAvailableTo();

        $this->assertEquals($expected, $actual);
    }

    private function getId(): Uuid
    {
        return new Uuid('aadcee95-6180-4924-a8eb-ed829d4957a2');
    }

    private function getMainLanguage(): Language
    {
        return new Language('nl');
    }

    private function getTitle(): TranslatedTitle
    {
        return new TranslatedTitle(
            $this->getMainLanguage(),
            new Title('foo')
        );
    }

    /**
     * @return Calendar
     */
    private function getCalendar()
    {
        return new SingleSubEventCalendar(
            new SubEvent(
                new DateRange(
                    DateTimeFactory::fromFormat('d/m/Y', '10/01/2018'),
                    DateTimeFactory::fromFormat('d/m/Y', '11/01/2018')
                ),
                new Status(StatusType::Available()),
                new BookingAvailability(BookingAvailabilityType::Available())
            )
        );
    }

    private function getTerms(): Categories
    {
        return new Categories(
            new Category(
                new CategoryID('0.50.1.0.0'),
                new CategoryLabel('Concert'),
                new CategoryDomain('eventtype')
            )
        );
    }

    /**
     * @return ImmutableOffer
     */
    private function getOffer()
    {
        return new MockImmutableOffer(
            $this->getId(),
            $this->getMainLanguage(),
            $this->getTitle(),
            $this->getCalendar(),
            $this->getTerms()
        );
    }
}
