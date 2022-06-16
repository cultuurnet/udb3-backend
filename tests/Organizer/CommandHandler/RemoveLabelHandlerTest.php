<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Organizer\Commands\RemoveLabel;
use CultuurNet\UDB3\Organizer\Events\LabelAdded;
use CultuurNet\UDB3\Organizer\Events\LabelRemoved;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

final class RemoveLabelHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): RemoveLabelHandler
    {
        return new RemoveLabelHandler(
            new OrganizerRepository(
                $eventStore,
                $eventBus
            ),
            $this->createMock(ReadRepositoryInterface::class)
        );
    }

    /**
     * @test
     */
    public function it_removes_an_attached_label(): void
    {
        $id = '60abd28a-8856-4167-ad59-014108259444';

        $this->scenario
            ->withAggregateId($id)
            ->given([
                $this->organizerCreated($id),
                new LabelAdded($id, 'foo'),
            ])
            ->when(new RemoveLabel($id, 'foo'))
            ->then([new LabelRemoved($id, 'foo')]);
    }

    /**
     * @test
     */
    public function it_removes_an_attached_invisible_label(): void
    {
        $id = '60abd28a-8856-4167-ad59-014108259444';
        $label = new Label(new LabelName('bar'), false);

        $this->scenario
            ->withAggregateId($id)
            ->given([
                $this->organizerCreated($id),
                new LabelAdded($id, 'bar', false),
            ])
            ->when(new RemoveLabel($id, $label->getName()->toString(), false))
            ->then([new LabelRemoved($id, 'bar', false)]);
    }

    /**
     * @test
     */
    public function it_does_not_remove_a_missing_label(): void
    {
        $id = '60abd28a-8856-4167-ad59-014108259444';
        $label = new Label(new LabelName('foo'));

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->organizerCreated($id)])
            ->when(new RemoveLabel($id, 'foo'))
            ->then([]);
    }

    private function organizerCreated(string $id): OrganizerCreated
    {
        return new OrganizerCreated(
            $id,
            'Organizer Title',
            'Kerkstraat 69',
            '9630',
            'Zottegem',
            'BE',
            ['phone'],
            ['email'],
            ['url'],
        );
    }
}
