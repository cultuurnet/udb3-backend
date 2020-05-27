<?php

namespace CultuurNet\UDB3\Place;

use Broadway\Domain\DomainEventStreamInterface;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\TraceableEventBus;
use Broadway\EventStore\EventStoreInterface;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\AgeRange;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\TypicalAgeRangeUpdated;
use CultuurNet\UDB3\SimpleEventBus;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Geography\Country;
use ValueObjects\Person\Age;

class PlaceRepositoryTest extends TestCase
{
    /**
     * @var PlaceRepository
     */
    private $placeRepository;

    /**
     * @var EventStoreInterface|MockObject
     */
    private $eventStore;

    /**
     * @var TraceableEventBus
     */
    private $eventBus;

    public function setUp()
    {
        parent::setUp();

        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->eventBus = new TraceableEventBus(new SimpleEventBus());

        $this->placeRepository = new PlaceRepository($this->eventStore, $this->eventBus);
    }

    /**
     * @test
     */
    public function it_should_save_multiple_places_in_a_single_transaction()
    {
        $place1 = Place::createPlace(
            '41c94f16-9edf-4eaf-914a-cfc01336b66e',
            new Language('nl'),
            new Title('Test title 1'),
            new EventType('0.0.0.1', 'Fake event type'),
            new Address(
                new Street('Kerkstraat 1'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                Country::fromNative('BE')
            ),
            new Calendar(CalendarType::PERMANENT())
        );

        $place1->updateTypicalAgeRange(
            new AgeRange(new Age(0), new Age(12))
        );

        $place2 = Place::createPlace(
            'aed3f3cd-e3de-4361-8e53-1099cce8fef6',
            new Language('nl'),
            new Title('Test title 2'),
            new EventType('0.0.0.1', 'Fake event type'),
            new Address(
                new Street('Kerkstraat 2'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                Country::fromNative('BE')
            ),
            new Calendar(CalendarType::PERMANENT())
        );

        $expectedEvents = [
            new PlaceCreated(
                '41c94f16-9edf-4eaf-914a-cfc01336b66e',
                new Language('nl'),
                new Title('Test title 1'),
                new EventType('0.0.0.1', 'Fake event type'),
                new Address(
                    new Street('Kerkstraat 1'),
                    new PostalCode('3000'),
                    new Locality('Leuven'),
                    Country::fromNative('BE')
                ),
                new Calendar(CalendarType::PERMANENT())
            ),
            new TypicalAgeRangeUpdated(
                '41c94f16-9edf-4eaf-914a-cfc01336b66e',
                new AgeRange(new Age(0), new Age(12))
            ),
            new PlaceCreated(
                'aed3f3cd-e3de-4361-8e53-1099cce8fef6',
                new Language('nl'),
                new Title('Test title 2'),
                new EventType('0.0.0.1', 'Fake event type'),
                new Address(
                    new Street('Kerkstraat 2'),
                    new PostalCode('3000'),
                    new Locality('Leuven'),
                    Country::fromNative('BE')
                ),
                new Calendar(CalendarType::PERMANENT())
            ),
        ];

        $actualEvents = [];

        $this->eventStore->expects($this->once())
            ->method('append')
            ->willReturnCallback(function ($firstId, DomainEventStreamInterface $eventStream) use (&$actualEvents) {
                $this->assertEquals('41c94f16-9edf-4eaf-914a-cfc01336b66e', $firstId);

                $actualEvents = array_map(
                    function (DomainMessage $domainMessage) {
                        return $domainMessage->getPayload();
                    },
                    iterator_to_array($eventStream->getIterator())
                );
            });

        $this->eventBus->trace();

        $this->placeRepository->saveMultiple($place1, $place2);

        $this->assertEquals($expectedEvents, $actualEvents);
        $this->assertEquals($expectedEvents, $this->eventBus->getEvents());
    }
}
