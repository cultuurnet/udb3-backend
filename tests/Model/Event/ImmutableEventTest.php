<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Event;

use CultuurNet\UDB3\Model\Place\ImmutablePlace;
use CultuurNet\UDB3\Model\Place\PlaceReference;
use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SingleSubEventCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use PHPUnit\Framework\TestCase;

class ImmutableEventTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_throw_an_exception_if_the_list_of_categories_is_empty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Categories should not be empty (eventtype required).');

        new ImmutableEvent(
            $this->getId(),
            $this->getMainLanguage(),
            $this->getTitle(),
            $this->getCalendar(),
            $this->getPlaceReference(),
            new Categories()
        );
    }

    /**
     * @test
     */
    public function it_should_return_the_injected_place_reference()
    {
        $placeReference = $this->getPlaceReference();
        $event = $this->getEvent();

        $this->assertEquals($placeReference, $event->getPlaceReference());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_place_reference()
    {
        $placeReference = $this->getPlaceReference();

        $updatedPlaceId = new UUID('23e965f1-f348-4915-9003-12162aa0e982');
        $updatedPlaceReference = PlaceReference::createWithPlaceId($updatedPlaceId);

        $event = $this->getEvent();
        $updatedEvent = $event->withPlaceReference($updatedPlaceReference);

        $this->assertNotEquals($event, $updatedEvent);
        $this->assertEquals($placeReference, $event->getPlaceReference());
        $this->assertEquals($updatedPlaceReference, $updatedEvent->getPlaceReference());
    }

    /**
     * @test
     */
    public function it_should_return_everyone_as_the_default_audience()
    {
        $event = $this->getEvent();
        $expected = AudienceType::everyone();
        $this->assertTrue($event->getAudienceType()->sameAs($expected));
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_audience()
    {
        $audience = AudienceType::everyone();
        $updatedAudience = AudienceType::members();

        $event = $this->getEvent();
        $updatedEvent = $event->withAudienceType($updatedAudience);

        $this->assertNotEquals($event, $updatedEvent);
        $this->assertTrue($event->getAudienceType()->sameAs($audience));
        $this->assertTrue($updatedEvent->getAudienceType()->sameAs($updatedAudience));
    }

    /**
     * @return UUID
     */
    private function getId()
    {
        return new UUID('aadcee95-6180-4924-a8eb-ed829d4957a2');
    }

    /**
     * @return Language
     */
    private function getMainLanguage()
    {
        return new Language('nl');
    }

    /**
     * @return TranslatedTitle
     */
    private function getTitle()
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
                    \DateTimeImmutable::createFromFormat('d/m/Y', '10/01/2018'),
                    \DateTimeImmutable::createFromFormat('d/m/Y', '11/01/2018')
                ),
                new Status(StatusType::Available())
            )
        );
    }

    /**
     * @return PlaceReference
     */
    private function getPlaceReference()
    {
        $title = new TranslatedTitle(
            $this->getMainLanguage(),
            new Title('N/A')
        );

        $address = new TranslatedAddress(
            $this->getMainLanguage(),
            new Address(
                new Street('Henegouwenkaai 41-43'),
                new PostalCode('1080'),
                new Locality('Brussel'),
                new CountryCode('BE')
            )
        );

        $dummyLocation = ImmutablePlace::createDummyLocation(
            $this->getMainLanguage(),
            $title,
            $address
        );

        return PlaceReference::createWithEmbeddedPlace($dummyLocation);
    }

    /**
     * @return Categories
     */
    private function getTerms()
    {
        return new Categories(
            new Category(
                new CategoryID('0.50.1.0.0'),
                new CategoryLabel('concert'),
                new CategoryDomain('eventtype')
            )
        );
    }

    /**
     * @return ImmutableEvent
     */
    private function getEvent()
    {
        return new ImmutableEvent(
            $this->getId(),
            $this->getMainLanguage(),
            $this->getTitle(),
            $this->getCalendar(),
            $this->getPlaceReference(),
            $this->getTerms()
        );
    }
}
