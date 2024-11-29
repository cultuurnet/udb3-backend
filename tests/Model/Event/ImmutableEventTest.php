<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Event;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Model\Place\PlaceReference;
use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SingleSubEventCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Online\AttendanceMode;
use PHPUnit\Framework\TestCase;

class ImmutableEventTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_throw_an_exception_if_the_list_of_categories_is_empty(): void
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
    public function it_should_return_the_injected_place_reference(): void
    {
        $placeReference = $this->getPlaceReference();
        $event = $this->getEvent();

        $this->assertEquals($placeReference, $event->getPlaceReference());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_place_reference(): void
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
    public function it_should_return_offline_as_the_default_attendanceMode(): void
    {
        $this->assertTrue($this->getEvent()->getAttendanceMode()->sameAs(AttendanceMode::offline()));
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_attendanceMode(): void
    {
        $event = $this->getEvent();
        $updatedEvent = $event->withAttendanceMode(AttendanceMode::mixed());

        $this->assertNotEquals($event, $updatedEvent);
        $this->assertTrue($event->getAttendanceMode()->sameAs(AttendanceMode::offline()));
        $this->assertTrue($updatedEvent->getAttendanceMode()->sameAs(AttendanceMode::mixed()));
    }

    /**
     * @test
     */
    public function it_should_return_everyone_as_the_default_audience(): void
    {
        $event = $this->getEvent();
        $expected = AudienceType::everyone();
        $this->assertTrue($event->getAudienceType()->sameAs($expected));
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_audience(): void
    {
        $audience = AudienceType::everyone();
        $updatedAudience = AudienceType::members();

        $event = $this->getEvent();
        $updatedEvent = $event->withAudienceType($updatedAudience);

        $this->assertNotEquals($event, $updatedEvent);
        $this->assertTrue($event->getAudienceType()->sameAs($audience));
        $this->assertTrue($updatedEvent->getAudienceType()->sameAs($updatedAudience));
    }

    private function getId(): UUID
    {
        return new UUID('aadcee95-6180-4924-a8eb-ed829d4957a2');
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

    private function getCalendar(): Calendar
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

    private function getPlaceReference(): PlaceReference
    {
        return PlaceReference::createWithPlaceId(new UUID('23f94284-550c-4fdd-8b66-8b0e2393283c'));
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

    private function getEvent(): ImmutableEvent
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
