<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Event\Commands\UpdateBirthYearRange;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\BirthYearRangeUpdated;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Audience\BirthYearRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

final class UpdateBirthYearRangeHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        return new UpdateBirthYearRangeHandler(new EventRepository($eventStore, $eventBus));
    }

    /**
     * @test
     */
    public function it_handles_updating_birth_year_range(): void
    {
        $eventId = '40021958-0ad8-46bd-8528-3ac3686818a1';
        $birthYearRange = new BirthYearRange(2014, 2020);

        $this->scenario
            ->withAggregateId($eventId)
            ->given([$this->getEventCreated($eventId)])
            ->when(new UpdateBirthYearRange($eventId, $birthYearRange))
            ->then([new BirthYearRangeUpdated($eventId, $birthYearRange)]);
    }

    /**
     * @test
     */
    public function it_replaces_existing_birth_year_range(): void
    {
        $eventId = '40021958-0ad8-46bd-8528-3ac3686818a1';
        $original = new BirthYearRange(2014, 2020);
        $updated = new BirthYearRange(2015, 2021);

        $this->scenario
            ->withAggregateId($eventId)
            ->given([$this->getEventCreated($eventId), new BirthYearRangeUpdated($eventId, $original)])
            ->when(new UpdateBirthYearRange($eventId, $updated))
            ->then([new BirthYearRangeUpdated($eventId, $updated)]);
    }

    /**
     * @test
     */
    public function it_ignores_updating_when_unchanged(): void
    {
        $eventId = '40021958-0ad8-46bd-8528-3ac3686818a1';
        $birthYearRange = new BirthYearRange(2014, 2020);

        $this->scenario
            ->withAggregateId($eventId)
            ->given([$this->getEventCreated($eventId), new BirthYearRangeUpdated($eventId, $birthYearRange)])
            ->when(new UpdateBirthYearRange($eventId, $birthYearRange))
            ->then([]);
    }

    private function getEventCreated(string $id): EventCreated
    {
        return new EventCreated(
            $id,
            new Language('nl'),
            'some representative title',
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new LocationId('bfc60a14-6208-4372-942e-86e63744769a'),
            new PermanentCalendar(new OpeningHours())
        );
    }
}
