<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Event\Commands\UpdateBirthdateRange;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\BirthdateRangeUpdated;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Audience\BirthdateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use DateTimeImmutable;

final class UpdateBirthdateRangeHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        return new UpdateBirthdateRangeHandler(new EventRepository($eventStore, $eventBus));
    }

    /**
     * @test
     */
    public function it_handles_updating_birthdate_range(): void
    {
        $eventId = '40021958-0ad8-46bd-8528-3ac3686818a1';
        $birthdateRange = new BirthdateRange(
            new DateTimeImmutable('2014-01-01'),
            new DateTimeImmutable('2020-12-31')
        );

        $this->scenario
            ->withAggregateId($eventId)
            ->given([$this->getEventCreated($eventId)])
            ->when(new UpdateBirthdateRange($eventId, $birthdateRange))
            ->then([new BirthdateRangeUpdated($eventId, $birthdateRange)]);
    }

    /**
     * @test
     */
    public function it_replaces_existing_birthdate_range(): void
    {
        $eventId = '40021958-0ad8-46bd-8528-3ac3686818a1';
        $original = new BirthdateRange(
            new DateTimeImmutable('2014-01-01'),
            new DateTimeImmutable('2020-12-31')
        );
        $updated = new BirthdateRange(
            new DateTimeImmutable('2015-01-01'),
            new DateTimeImmutable('2021-12-31')
        );

        $this->scenario
            ->withAggregateId($eventId)
            ->given([$this->getEventCreated($eventId), new BirthdateRangeUpdated($eventId, $original)])
            ->when(new UpdateBirthdateRange($eventId, $updated))
            ->then([new BirthdateRangeUpdated($eventId, $updated)]);
    }

    /**
     * @test
     */
    public function it_ignores_updating_when_unchanged(): void
    {
        $eventId = '40021958-0ad8-46bd-8528-3ac3686818a1';
        $birthdateRange = new BirthdateRange(
            new DateTimeImmutable('2014-01-01'),
            new DateTimeImmutable('2020-12-31')
        );

        $this->scenario
            ->withAggregateId($eventId)
            ->given([$this->getEventCreated($eventId), new BirthdateRangeUpdated($eventId, $birthdateRange)])
            ->when(new UpdateBirthdateRange($eventId, $birthdateRange))
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
