<?php

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\LabelAdded;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Label\LabelServiceInterface;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Commands\AddLabel;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Place\PlaceRepository;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

final class AddLabelHandlerTest extends CommandHandlerScenarioTestCase
{
    /**
     * @var Entity[]
     */
    private $mockedLabelReadModels;

    /**
     * @var LabelServiceInterface|MockObject
     */
    private $labelService;

    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): AddLabelHandler
    {
        $labelRepository = $this->createMock(ReadRepositoryInterface::class);
        $labelRepository
            ->method('getByName')
            ->willReturnCallback(
                function (StringLiteral $name) {
                    return $this->mockedLabelReadModels[$name->toNative()] ?? null;
                }
            );

        $this->labelService = $this->createMock(LabelServiceInterface::class);

        return new AddLabelHandler(
            new OfferRepository(
                new EventRepository($eventStore, $eventBus),
                new PlaceRepository($eventStore, $eventBus)
            ),
            $this->labelService,
            $labelRepository
        );
    }

    /**
     * @test
     */
    public function it_should_use_existing_visibility_for_existing_labels(): void
    {
        $this->mockedLabelReadModels['foo'] = new Entity(
            new UUID(),
            new StringLiteral('foo'),
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PUBLIC()
        );

        $this->labelService
            ->method('createLabelAggregateIfNew')
            ->with(new LabelName('foo'), false);

        $id = '4c6d4bb8-702b-49f1-b0ca-e51eb09a1c19';

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->eventCreated($id)])
            ->when(new AddLabel($id, new Label('foo', false)))
            ->then([new LabelAdded($id, new Label('foo', true))]);
    }

    /**
     * @test
     */
    public function it_should_use_visibility_from_the_command_if_the_label_did_not_exist_before(): void
    {
        $this->labelService->expects($this->at(0))
            ->method('createLabelAggregateIfNew')
            ->with(new LabelName('visible'), true);

        $this->labelService->expects($this->at(1))
            ->method('createLabelAggregateIfNew')
            ->with(new LabelName('hidden'), false);

        $id = '4c6d4bb8-702b-49f1-b0ca-e51eb09a1c19';

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->eventCreated($id)])
            ->when(new AddLabel($id, new Label('visible', true)))
            ->then([new LabelAdded($id, new Label('visible', true))])
            ->when(new AddLabel($id, new Label('hidden', false)))
            ->then([new LabelAdded($id, new Label('hidden', false))]);
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
