<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Event\Commands\RemoveTheme;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\ThemeRemoved;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

class RemoveThemeHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): RemoveThemeHandler
    {
        return new RemoveThemeHandler(new EventRepository($eventStore, $eventBus));
    }

    /**
     * @test
     */
    public function it_handles_remove_theme(): void
    {
        $eventId = '208dbe98-ffaa-41cb-9ada-7ec8e0651f48';

        $this->scenario
            ->withAggregateId($eventId)
            ->given([$this->getEventCreated(
                $eventId,
                new Category(new CategoryID('1.8.1.0.0'), new CategoryLabel('Rock'), CategoryDomain::theme())
            ),
            ])
            ->when(new RemoveTheme($eventId))
            ->then([new ThemeRemoved($eventId)]);
    }

    /**
     * @test
     */
    public function it_removes_a_theme_only_once(): void
    {
        $eventId = '208dbe98-ffaa-41cb-9ada-7ec8e0651f48';

        $this->scenario
            ->withAggregateId($eventId)
            ->given([
                $this->getEventCreated(
                    $eventId,
                    new Category(new CategoryID('1.8.1.0.0'), new CategoryLabel('Rock'), CategoryDomain::theme())
                ),
                new ThemeRemoved($eventId),
            ])
            ->when(new RemoveTheme($eventId))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_removes_a_theme_only_when_one_is_present(): void
    {
        $eventId = '208dbe98-ffaa-41cb-9ada-7ec8e0651f48';

        $this->scenario
            ->withAggregateId($eventId)
            ->given([$this->getEventCreated($eventId, null), new ThemeRemoved($eventId)])
            ->when(new RemoveTheme($eventId))
            ->then([]);
    }

    private function getEventCreated(string $eventId, ?Category $theme): EventCreated
    {
        return new EventCreated(
            $eventId,
            new Language('nl'),
            'some representative title',
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new PermanentCalendar(new OpeningHours()),
            $theme
        );
    }
}
