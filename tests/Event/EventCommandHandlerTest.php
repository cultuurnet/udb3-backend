<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use Broadway\Repository\Repository;
use Cake\Chronos\Chronos;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\Commands\CreateEvent;
use CultuurNet\UDB3\Event\Commands\DeleteEvent;
use CultuurNet\UDB3\Event\Commands\EventCommandFactory;
use CultuurNet\UDB3\Event\Commands\UpdateAudience;
use CultuurNet\UDB3\Event\Commands\UpdateCalendar;
use CultuurNet\UDB3\Event\Commands\UpdateDescription;
use CultuurNet\UDB3\Event\Commands\UpdateLocation;
use CultuurNet\UDB3\Event\Commands\UpdateMajorInfo;
use CultuurNet\UDB3\Event\Commands\UpdateTitle;
use CultuurNet\UDB3\Event\Events\AudienceUpdated;
use CultuurNet\UDB3\Event\Events\CalendarUpdated;
use CultuurNet\UDB3\Event\Events\DescriptionTranslated;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventDeleted;
use CultuurNet\UDB3\Event\Events\LocationUpdated;
use CultuurNet\UDB3\Event\Events\MajorInfoUpdated;
use CultuurNet\UDB3\Event\Events\PriceInfoUpdated;
use CultuurNet\UDB3\Event\Events\TitleTranslated;
use CultuurNet\UDB3\Event\ValueObjects\Audience;
use CultuurNet\UDB3\Event\ValueObjects\AudienceType;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Media\MediaManager;
use CultuurNet\UDB3\OfferCommandHandlerTestTrait;
use CultuurNet\UDB3\PriceInfo\BasePrice;
use CultuurNet\UDB3\PriceInfo\Price;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;
use DateTimeInterface;
use ValueObjects\Money\Currency;

class EventCommandHandlerTest extends CommandHandlerScenarioTestCase
{
    use OfferCommandHandlerTestTrait;

    /**
     * @var EventCommandFactory
     */
    private $commandFactory;

    protected function createCommandHandler(
        EventStore $eventStore,
        EventBus $eventBus
    ) {
        $repository = new EventRepository(
            $eventStore,
            $eventBus
        );

        $this->organizerRepository = $this->createMock(Repository::class);

        $this->mediaManager = $this->createMock(MediaManager::class);

        $this->commandFactory = new EventCommandFactory();

        return new EventCommandHandler(
            $repository,
            $this->organizerRepository,
            $this->mediaManager
        );
    }

    private function factorOfferCreated($id)
    {
        return new EventCreated(
            $id,
            new Language('nl'),
            new Title('some representative title'),
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new Calendar(CalendarType::PERMANENT())
        );
    }

    /**
     * @test
     */
    public function it_should_create_a_new_event()
    {
        $id = '5e36d2f2-b5de-4f5e-81b3-a129d996e9b6';
        $language = new Language('nl');
        $title = new Title('some representative title');
        $type = new EventType('0.50.4.0.0', 'concert');
        $location = new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015');
        $calendar = new Calendar(CalendarType::PERMANENT());
        $theme = new Theme('0.1.0.1.0.1', 'blues');

        $now = Chronos::now();
        Chronos::setTestNow($now);
        $publicationDate = $now;

        $command = new CreateEvent(
            $id,
            $language,
            $title,
            $type,
            $location,
            $calendar,
            $theme,
            $publicationDate
        );

        $this->scenario
            ->withAggregateId($id)
            ->when($command)
            ->then([new EventCreated($id, $language, $title, $type, $location, $calendar, $theme, $now)]);

        // reset mocked time
        Chronos::setTestNow();
    }

    /**
     * @test
     */
    public function it_can_translate_the_title_of_an_event_by_updating_with_a_foreign_language()
    {
        $id = '1';
        $title = new Title('Voorbeeld');
        $language = new Language('fr');
        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $this->factorOfferCreated($id),
                ]
            )
            ->when(new UpdateTitle($id, $language, $title))
            ->then(
                [
                    new TitleTranslated($id, $language, $title),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_translate_the_description_of_an_event_by_updating_with_a_foreign_language()
    {
        $id = '1';
        $description = new Description('Lorem ipsum dolor si amet...');
        $language = new Language('fr');
        $this->scenario
            ->withAggregateId($id)
            ->given(
                [$this->factorOfferCreated($id)]
            )
            ->when(new UpdateDescription($id, $language, $description))
            ->then([new DescriptionTranslated($id, $language, $description)]);
    }

    /**
     * @test
     */
    public function it_can_update_major_info_of_an_event()
    {
        $id = '1';
        $title = new Title('foo');
        $eventType = new EventType('0.50.4.0.0', 'concert');
        $location = new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015');
        $calendar = new Calendar(CalendarType::PERMANENT());

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [$this->factorOfferCreated($id)]
            )
            ->when(
                new UpdateMajorInfo($id, $title, $eventType, $location, $calendar)
            )
            ->then([new MajorInfoUpdated($id, $title, $eventType, $location, $calendar)]);
    }

    /**
     * @test
     */
    public function it_updates_the_audience_type_when_setting_the_location_to_a_dummy_location_via_major_info()
    {
        LocationId::setDummyPlaceForEducationIds(['6f87ce4c-bd39-4c5e-92b5-a9f8bdf4aa31']);

        $id = '1';
        $title = new Title('foo');
        $eventType = new EventType('0.50.4.0.0', 'concert');
        $location = new LocationId('6f87ce4c-bd39-4c5e-92b5-a9f8bdf4aa31');
        $calendar = new Calendar(CalendarType::PERMANENT());

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [$this->factorOfferCreated($id)]
            )
            ->when(
                new UpdateMajorInfo($id, $title, $eventType, $location, $calendar)
            )
            ->then(
                [
                    new MajorInfoUpdated($id, $title, $eventType, $location, $calendar),
                    new AudienceUpdated($id, new Audience(AudienceType::EDUCATION())),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_update_the_calendar_of_an_event()
    {
        $eventId = '0f4ea9ad-3681-4f3b-adc2-4b8b00dd845a';

        $calendar = new Calendar(
            CalendarType::PERIODIC(),
            \DateTime::createFromFormat(DateTimeInterface::ATOM, '2020-01-26T11:11:11+01:00'),
            \DateTime::createFromFormat(DateTimeInterface::ATOM, '2020-01-27T12:12:12+01:00')
        );

        $this->scenario
            ->withAggregateId($eventId)
            ->given(
                [
                    $this->factorOfferCreated($eventId),
                ]
            )
            ->when(
                new UpdateCalendar($eventId, $calendar)
            )
            ->then(
                [
                    new CalendarUpdated($eventId, $calendar),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_update_location_of_an_event()
    {
        $eventId = '3ed90f18-93a3-4340-981d-12e57efa0211';

        $locationId = new LocationId('57738178-28a5-4afb-90c0-fd0beba172a8');

        $this->scenario
            ->withAggregateId($eventId)
            ->given(
                [
                    $this->factorOfferCreated($eventId),
                ]
            )
            ->when(
                new UpdateLocation($eventId, $locationId)
            )
            ->then(
                [
                    new LocationUpdated($eventId, $locationId),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_update_audience()
    {
        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';

        $this->scenario
            ->withAggregateId($eventId)
            ->given(
                [
                    $this->factorOfferCreated($eventId),
                ]
            )
            ->when(
                new UpdateAudience(
                    $eventId,
                    new Audience(AudienceType::EDUCATION())
                )
            )
            ->then(
                [
                    new AudienceUpdated(
                        $eventId,
                        new Audience(AudienceType::EDUCATION())
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_update_audience_after_switching_from_a_dummy_location_to_another_location_using_major_info()
    {
        // Mark the id used by $this->factorOfferCreated() as a dummy location.
        LocationId::setDummyPlaceForEducationIds(['d0cd4e9d-3cf1-4324-9835-2bfba63ac015']);

        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';

        $this->scenario
            ->withAggregateId($eventId)
            ->given(
                [
                    $this->factorOfferCreated($eventId),
                    new UpdateMajorInfo(
                        $eventId,
                        new Title('some representative title'),
                        new EventType('0.50.4.0.0', 'concert'),
                        new LocationId('345afdf3-e670-4aa6-a4d2-b95ca081c18d'),
                        new Calendar(CalendarType::PERMANENT())
                    ),
                ]
            )
            ->when(
                new UpdateAudience(
                    $eventId,
                    new Audience(AudienceType::EDUCATION())
                )
            )
            ->then(
                [
                    new AudienceUpdated(
                        $eventId,
                        new Audience(AudienceType::EDUCATION())
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_should_ignore_updating_the_audience_when_the_same_audience_type_is_already_set()
    {
        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';

        $this->scenario
            ->withAggregateId($eventId)
            ->given(
                [
                    $this->factorOfferCreated($eventId),
                    new AudienceUpdated(
                        $eventId,
                        new Audience(AudienceType::EDUCATION())
                    ),
                ]
            )
            ->when(
                new UpdateAudience(
                    $eventId,
                    new Audience(AudienceType::EDUCATION())
                )
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_can_update_price_info()
    {
        $id = '1';

        $priceInfo = new PriceInfo(
            new BasePrice(
                Price::fromFloat(10.5),
                Currency::fromNative('EUR')
            )
        );

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $this->factorOfferCreated($id),
                ]
            )
            ->when(
                $this->commandFactory->createUpdatePriceInfoCommand($id, $priceInfo)
            )
            ->then(
                [
                    new PriceInfoUpdated($id, $priceInfo),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_delete_events()
    {
        $id = '1';
        $this->scenario
            ->withAggregateId($id)
            ->given(
                [$this->factorOfferCreated($id)]
            )
            ->when(
                new DeleteEvent($id)
            )
            ->then([new EventDeleted($id)]);
    }
}
