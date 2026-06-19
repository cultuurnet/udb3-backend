<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Event\Commands\UpdateChildrenOnly;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\ChildrenOnlyUpdated;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

final class UpdateChildrenOnlyHandlerTest extends CommandHandlerScenarioTestCase
{
    private const EVENT_ID = '40021958-0ad8-46bd-8528-3ac3686818a1';

    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        return new UpdateChildrenOnlyHandler(new EventRepository($eventStore, $eventBus));
    }

    /**
     * @test
     */
    public function it_sets_children_only_to_true(): void
    {
        $this->scenario
            ->withAggregateId(self::EVENT_ID)
            ->given([$this->getEventCreated()])
            ->when(new UpdateChildrenOnly(self::EVENT_ID, true))
            ->then([new ChildrenOnlyUpdated(self::EVENT_ID, true)]);
    }

    /**
     * @test
     */
    public function it_sets_children_only_to_false(): void
    {
        $this->scenario
            ->withAggregateId(self::EVENT_ID)
            ->given([
                $this->getEventCreated(),
                new ChildrenOnlyUpdated(self::EVENT_ID, true),
            ])
            ->when(new UpdateChildrenOnly(self::EVENT_ID, false))
            ->then([new ChildrenOnlyUpdated(self::EVENT_ID, false)]);
    }

    /**
     * @test
     */
    public function it_does_not_emit_when_value_is_unchanged(): void
    {
        $this->scenario
            ->withAggregateId(self::EVENT_ID)
            ->given([
                $this->getEventCreated(),
                new ChildrenOnlyUpdated(self::EVENT_ID, true),
            ])
            ->when(new UpdateChildrenOnly(self::EVENT_ID, true))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_does_not_emit_when_setting_false_on_new_event(): void
    {
        $this->scenario
            ->withAggregateId(self::EVENT_ID)
            ->given([$this->getEventCreated()])
            ->when(new UpdateChildrenOnly(self::EVENT_ID, false))
            ->then([]);
    }

    private function getEventCreated(): EventCreated
    {
        return new EventCreated(
            self::EVENT_ID,
            new Language('nl'),
            'some representative title',
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new LocationId('bfc60a14-6208-4372-942e-86e63744769a'),
            new PermanentCalendar(new OpeningHours())
        );
    }
}
