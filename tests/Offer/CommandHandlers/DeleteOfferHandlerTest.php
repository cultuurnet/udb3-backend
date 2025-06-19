<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventDeleted;
use CultuurNet\UDB3\Event\Events\Moderation\Approved;
use CultuurNet\UDB3\Event\Events\Moderation\Published;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Commands\DeleteOffer;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Place\PlaceRepository;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\CannotDeleteUiTPASPlace;
use CultuurNet\UDB3\Security\Permission\DeleteUiTPASPlaceVoter;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

class DeleteOfferHandlerTest extends CommandHandlerScenarioTestCase
{
    private const EVENT_ID = '208dbe98-ffaa-41cb-9ada-7ec8e0651f48';

    private DeleteUiTPASPlaceVoter&MockObject $validator;
    private string $userId;

    public function setUp(): void
    {
        $this->validator = $this->createMock(DeleteUiTPASPlaceVoter::class);
        $this->userId = Uuid::uuid4()->toString();

        parent::setUp();
    }

    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): DeleteOfferHandler
    {
        return new DeleteOfferHandler(
            new OfferRepository(
                new EventRepository($eventStore, $eventBus),
                new PlaceRepository($eventStore, $eventBus)
            ),
            $this->validator,
            $this->userId
        );
    }

    /**
     * @test
     */
    public function it_handles_delete_of_a_draft_offer(): void
    {
        $this->validator->expects($this->once())
            ->method('isAllowed')
            ->with(Permission::aanbodVerwijderen(), self::EVENT_ID, $this->userId)
            ->willReturn(true);

        $eventId = self::EVENT_ID;

        $this->scenario
            ->withAggregateId($eventId)
            ->given(
                [
                    $this->getEventCreated($eventId),
                ]
            )
            ->when(new DeleteOffer($eventId))
            ->then(
                [
                    new EventDeleted($eventId),
                ]
            );
    }

    /**
     * @test
     */
    public function it_handles_delete_of_a_ready_for_validation_offer(): void
    {
        $this->validator->expects($this->once())
            ->method('isAllowed')
            ->with(Permission::aanbodVerwijderen(), self::EVENT_ID, $this->userId)
            ->willReturn(true);

        $eventId = self::EVENT_ID;

        $this->scenario
            ->withAggregateId($eventId)
            ->given(
                [
                    $this->getEventCreated($eventId),
                    new Published($eventId, new DateTimeImmutable()),
                ]
            )
            ->when(new DeleteOffer($eventId))
            ->then(
                [
                    new EventDeleted($eventId),
                ]
            );
    }

    /**
     * @test
     */
    public function it_handles_delete_of_an_approved_offer(): void
    {
        $this->validator->expects($this->once())
            ->method('isAllowed')
            ->with(Permission::aanbodVerwijderen(), self::EVENT_ID, $this->userId)
            ->willReturn(true);

        $eventId = self::EVENT_ID;

        $this->scenario
            ->withAggregateId($eventId)
            ->given(
                [
                    $this->getEventCreated($eventId),
                    new Published($eventId, new DateTimeImmutable()),
                    new Approved($eventId),
                ]
            )
            ->when(new DeleteOffer($eventId))
            ->then(
                [
                    new EventDeleted($eventId),
                ]
            );
    }

    /**
     * @test
     */
    public function it_does_not_delete_a_deleted_offer_again(): void
    {
        $this->validator->expects($this->once())
            ->method('isAllowed')
            ->with(Permission::aanbodVerwijderen(), self::EVENT_ID, $this->userId)
            ->willReturn(true);

        $eventId = self::EVENT_ID;

        $this->scenario
            ->withAggregateId($eventId)
            ->given(
                [
                    $this->getEventCreated($eventId),
                    new Published($eventId, new DateTimeImmutable()),
                    new Approved($eventId),
                    new EventDeleted($eventId),
                ]
            )
            ->when(new DeleteOffer($eventId))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_prevents_deletion_when_voter_fails(): void
    {
        $this->validator->expects($this->once())
            ->method('isAllowed')
            ->with(Permission::aanbodVerwijderen(), self::EVENT_ID, $this->userId)
            ->willReturn(false);

        $eventId = self::EVENT_ID;

        $this->expectException(CannotDeleteUiTPASPlace::class);

        $this->scenario
            ->withAggregateId($eventId)
            ->given(
                [
                    $this->getEventCreated($eventId),
                ]
            )
            ->when(new DeleteOffer($eventId));
    }

    private function getEventCreated(string $eventId): EventCreated
    {
        return new EventCreated(
            $eventId,
            new Language('nl'),
            'some representative title',
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new PermanentCalendar(new OpeningHours())
        );
    }
}
