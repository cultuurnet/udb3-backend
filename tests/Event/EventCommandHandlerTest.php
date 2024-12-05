<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use Broadway\Repository\Repository;
use Cake\Chronos\Chronos;
use CultureFeed_Cdb_Xml;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Event\Commands\CreateEvent;
use CultuurNet\UDB3\Event\Commands\UpdateDescription;
use CultuurNet\UDB3\Event\Commands\UpdateLocation;
use CultuurNet\UDB3\Event\Commands\UpdateMajorInfo;
use CultuurNet\UDB3\Event\Events\AudienceUpdated;
use CultuurNet\UDB3\Event\Events\DescriptionTranslated;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\LocationUpdated;
use CultuurNet\UDB3\Event\Events\MajorInfoUpdated;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\OfferCommandHandlerTestTrait;
use CultuurNet\UDB3\SampleFiles;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;

class EventCommandHandlerTest extends CommandHandlerScenarioTestCase
{
    use OfferCommandHandlerTestTrait;

    protected function createCommandHandler(
        EventStore $eventStore,
        EventBus $eventBus
    ): EventCommandHandler {
        $repository = new EventRepository(
            $eventStore,
            $eventBus
        );

        $this->organizerRepository = $this->createMock(Repository::class);

        $this->mediaManager = $this->createMock(MediaManagerInterface::class);

        return new EventCommandHandler(
            $repository,
            $this->organizerRepository,
            $this->mediaManager
        );
    }

    private function factorOfferCreated(string $id): EventCreated
    {
        return new EventCreated(
            $id,
            new Language('nl'),
            'some representative title',
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new Calendar(CalendarType::permanent())
        );
    }

    /**
     * @test
     */
    public function it_should_create_a_new_event(): void
    {
        $id = '5e36d2f2-b5de-4f5e-81b3-a129d996e9b6';
        $language = new Language('nl');
        $title = new Title('some representative title');
        $type = new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType());
        $location = new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015');
        $calendar = new Calendar(CalendarType::permanent());
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
            ->then([new EventCreated($id, $language, $title->toString(), $type, $location, $calendar, $theme, $now)]);

        // reset mocked time
        Chronos::setTestNow();
    }

    /**
     * @test
     */
    public function it_can_translate_the_description_of_an_event_by_updating_with_a_foreign_language(): void
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
    public function it_can_update_major_info_of_an_event(): void
    {
        $id = '1';
        $title = new Title('foo');
        $eventType = new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType());
        $location = new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015');
        $calendar = new Calendar(CalendarType::permanent());

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [$this->factorOfferCreated($id)]
            )
            ->when(
                new UpdateMajorInfo($id, $title, $eventType, $location, $calendar)
            )
            ->then([new MajorInfoUpdated($id, $title->toString(), $eventType, $location, $calendar)]);
    }

    /**
     * @test
     */
    public function it_updates_the_audience_type_when_setting_the_location_to_a_dummy_location_via_major_info(): void
    {
        LocationId::setDummyPlaceForEducationIds(['6f87ce4c-bd39-4c5e-92b5-a9f8bdf4aa31']);

        $id = '1';
        $title = new Title('foo');
        $eventType = new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType());
        $location = new LocationId('6f87ce4c-bd39-4c5e-92b5-a9f8bdf4aa31');
        $calendar = new Calendar(CalendarType::permanent());

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
                    new MajorInfoUpdated($id, $title->toString(), $eventType, $location, $calendar),
                    new AudienceUpdated($id, AudienceType::education()),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_update_location_of_an_event(): void
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
     * @bugfix https://jira.uitdatabank.be/browse/III-4702
     */
    public function it_can_update_location_of_an_event_imported_from_xml(): void
    {
        $eventId = '3ed90f18-93a3-4340-981d-12e57efa0211';
        $locationId = new LocationId('57738178-28a5-4afb-90c0-fd0beba172a8');

        $this->scenario
            ->withAggregateId($eventId)
            ->given(
                [
                    new EventImportedFromUDB2(
                        $eventId,
                        SampleFiles::read(__DIR__ . '/samples/EventTest.cdbxml.xml'),
                        CultureFeed_Cdb_Xml::namespaceUriForVersion('3.2')
                    ),
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
}
