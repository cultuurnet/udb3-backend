<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Organizer\Commands\RemoveLabel;
use CultuurNet\UDB3\Organizer\Events\LabelAdded;
use CultuurNet\UDB3\Organizer\Events\LabelRemoved;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\OrganizerRepository;
use CultuurNet\UDB3\Title;
use ValueObjects\Geography\Country;
use ValueObjects\Identity\UUID;

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
        $label = new Label('foo', true);

        $this->scenario
            ->withAggregateId($id)
            ->given([
                $this->organizerCreated($id),
                new LabelAdded($id, $label),
            ])
            ->when(new RemoveLabel($id, $label))
            ->then([new LabelRemoved($id, $label)]);
    }

    /**
     * @test
     */
    public function it_removes_an_attached_invisible_label(): void
    {
        $id = '60abd28a-8856-4167-ad59-014108259444';
        $label = new Label('bar', false);

        $this->scenario
            ->withAggregateId($id)
            ->given([
                $this->organizerCreated($id),
                new LabelAdded($id, $label),
            ])
            ->when(new RemoveLabel($id, $label))
            ->then([new LabelRemoved($id, $label)]);
    }

    /**
     * @test
     */
    public function it_does_not_remove_a_missing_label(): void
    {
        $id = '60abd28a-8856-4167-ad59-014108259444';
        $label = new Label('foo');

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->organizerCreated($id)])
            ->when(new RemoveLabel($id, $label))
            ->then([]);
    }

    private function organizerCreated(string $id): OrganizerCreated
    {
        return new OrganizerCreated(
            $id,
            new Title('Organizer Title'),
            [
                new Address(
                    new Street('Kerkstraat 69'),
                    new PostalCode('9630'),
                    new Locality('Zottegem'),
                    Country::fromNative('BE')
                ),
            ],
            ['phone'],
            ['email'],
            ['url']
        );
    }
}
