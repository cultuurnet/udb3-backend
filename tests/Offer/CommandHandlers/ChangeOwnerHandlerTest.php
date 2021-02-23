<?php

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\OwnerChanged;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;
use CultuurNet\UDB3\Offer\Commands\ChangeOwner;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Offer\ReadModel\Permission\PermissionQueryInterface;
use CultuurNet\UDB3\Place\PlaceRepository;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use ValueObjects\StringLiteral\StringLiteral;

class ChangeOwnerHandlerTest extends CommandHandlerScenarioTestCase
{
    /**
     * @var PermissionQueryInterface|MockObject
     */
    private $permissionQuery;

    protected function createCommandHandler(
        EventStore $eventStore,
        EventBus $eventBus
    ): CommandHandler {
        $repository = new OfferRepository(
            new EventRepository($eventStore, $eventBus),
            new PlaceRepository($eventStore, $eventBus)
        );

        $this->permissionQuery = $this->createMock(PermissionQueryInterface::class);

        return new ChangeOwnerHandler($repository, $this->permissionQuery);
    }

    /**
     * @test
     */
    public function it_only_handles_change_owner_commands(): void
    {
        $randomCommand = $this->createMock(AbstractCommand::class);

        $this->permissionQuery->expects($this->never())
            ->method('getEditableOffers');

        $id = 'f818db49-1484-4513-a534-d22c2ca88026';

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->eventCreated($id)])
            ->when($randomCommand)
            ->then([]);
    }

    /**
     * @test
     */
    public function it_does_nothing_if_the_new_owner_is_the_current_owner_based_on_the_permission_read_model(): void
    {
        $id = 'f818db49-1484-4513-a534-d22c2ca88026';
        $newOwner = 'auth0|598e7dc9-523b-4d58-b6ea-b4aad5a4a291';

        $this->permissionQuery
            ->method('getEditableOffers')
            ->with($newOwner)
            ->willReturn([new StringLiteral($id)]);

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->eventCreated($id)])
            ->when(new ChangeOwner($id, $newOwner))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_changes_the_owner(): void
    {
        $id = 'f818db49-1484-4513-a534-d22c2ca88026';
        $otherId = '3a97236f-21a6-45ef-9f7c-8ac23d151b45';
        $newOwner = 'auth0|598e7dc9-523b-4d58-b6ea-b4aad5a4a291';

        $this->permissionQuery
            ->method('getEditableOffers')
            ->with($newOwner)
            ->willReturn([new StringLiteral($otherId)]);

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->eventCreated($id)])
            ->when(new ChangeOwner($id, $newOwner))
            ->then([new OwnerChanged($id, $newOwner)]);
    }

    private function eventCreated(string $id): EventCreated
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
}
