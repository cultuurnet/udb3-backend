<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Event\Commands\UpdateTheme;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\ThemeUpdated;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\Import\Taxonomy\Category\CategoryNotFound;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

class UpdateThemeHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): UpdateThemeHandler
    {
        return new UpdateThemeHandler(new EventRepository($eventStore, $eventBus));
    }

    /**
     * @test
     */
    public function it_handles_update_theme_with_valid_theme_category_id(): void
    {
        $id = '1';
        $originalTheme = null;
        $command = new UpdateTheme($id, '1.8.3.5.0');
        $expectedEvent = new ThemeUpdated($id, new Category(new CategoryID('1.8.3.5.0'), new CategoryLabel('Amusementsmuziek'), CategoryDomain::theme()));

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->getEventCreated($id, $originalTheme)])
            ->when($command)
            ->then([$expectedEvent]);
    }

    /**
     * @test
     */
    public function it_updates_a_different_existing_theme(): void
    {
        $id = '1';
        $originalTheme = new Category(new CategoryID('1.8.3.3.0'), new CategoryLabel('Dance muziek'), CategoryDomain::theme());
        $command = new UpdateTheme($id, '1.8.3.5.0');
        $expectedEvent = new ThemeUpdated($id, new Category(new CategoryID('1.8.3.5.0'), new CategoryLabel('Amusementsmuziek'), CategoryDomain::theme()));

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->getEventCreated($id, $originalTheme)])
            ->when($command)
            ->then([$expectedEvent]);
    }

    /**
     * @test
     */
    public function it_does_not_record_a_new_event_if_the_theme_is_the_same_as_before(): void
    {
        $id = '1';
        $originalTheme = new Category(new CategoryID('1.8.3.3.0'), new CategoryLabel('Dance muziek'), CategoryDomain::theme());
        $command = new UpdateTheme($id, '1.8.3.3.0');

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->getEventCreated($id, $originalTheme)])
            ->when($command)
            ->then([]);
    }

    /**
     * @test
     */
    public function it_throws_if_an_unknown_theme_id_is_given(): void
    {
        $id = '1';
        $command = new UpdateTheme($id, 'foobar');

        $this->expectException(CategoryNotFound::class);

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->getEventCreated($id)])
            ->when($command)
            ->then([]);
    }

    private function getEventCreated(string $id, ?Category $theme = null): EventCreated
    {
        return new EventCreated(
            $id,
            new Language('nl'),
            'some representative title',
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new PermanentCalendar(new OpeningHours()),
            $theme
        );
    }
}
