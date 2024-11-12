<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Calendar\CalendarType;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventDeleted;
use CultuurNet\UDB3\Event\Events\Moderation\Approved;
use CultuurNet\UDB3\Event\Events\Moderation\Published;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Commands\DeleteOffer;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Offer\Validator\OfferCommandValidator;
use CultuurNet\UDB3\Place\PlaceRepository;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;

class DeleteOfferHandlerTest extends CommandHandlerScenarioTestCase
{
    private const EVENT_ID = '208dbe98-ffaa-41cb-9ada-7ec8e0651f48';

    /**
     * @var OfferCommandValidator&MockObject
     */
    private $validator;

    public function setUp(): void
    {
        $this->validator = $this->createMock(OfferCommandValidator::class);

        parent::setUp();
    }

    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): DeleteOfferHandler
    {
        return new DeleteOfferHandler(
            new OfferRepository(
                new EventRepository($eventStore, $eventBus),
                new PlaceRepository($eventStore, $eventBus)
            ),
            $this->validator
        );
    }

    /**
     * @test
     */
    public function it_handles_delete_of_a_draft_offer(): void
    {
        $this->validator->expects($this->once())
            ->method('isValid')
            ->with(self::EVENT_ID)
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
            ->method('isValid')
            ->with(self::EVENT_ID)
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
            ->method('isValid')
            ->with(self::EVENT_ID)
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
            ->method('isValid')
            ->with(self::EVENT_ID)
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
    public function it_prevents_deletion_when_validator_fails(): void
    {
        $this->validator->expects($this->once())
            ->method('isValid')
            ->with(self::EVENT_ID)
            ->willReturn(false);

        $this->validator->expects($this->once())
            ->method('getApiProblem')
            ->willReturn(ApiProblem::cannotDeleteUitpasPlace());

        $eventId = self::EVENT_ID;

        $this->expectException(ApiProblem::class);

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
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new Calendar(CalendarType::PERMANENT())
        );
    }
}
