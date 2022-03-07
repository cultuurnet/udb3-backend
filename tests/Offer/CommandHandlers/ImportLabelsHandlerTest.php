<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\LabelAdded;
use CultuurNet\UDB3\Event\Events\LabelRemoved;
use CultuurNet\UDB3\Event\Events\LabelsImported;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Label as DeprecatedLabel;
use CultuurNet\UDB3\Label\LabelImportPreProcessor;
use CultuurNet\UDB3\Label\LabelServiceInterface;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\LabelName as DeprecatedLabelName;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Offer\Commands\ImportLabels;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Place\PlaceRepository;
use CultuurNet\UDB3\StringLiteral;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;

final class ImportLabelsHandlerTest extends CommandHandlerScenarioTestCase
{
    private MockObject $labelService;

    private MockObject $labelPermissionRepository;

    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): ImportLabelsHandler
    {
        $this->labelService = $this->createMock(LabelServiceInterface::class);

        $this->labelPermissionRepository = $this->createMock(ReadRepositoryInterface::class);
        $this->labelPermissionRepository->expects($this->any())
            ->method('canUseLabel')
            ->willReturnCallback(
                function (StringLiteral $userId, StringLiteral $labelName) {
                    return $labelName->toNative() !== 'not_allowed';
                }
            );

        return new ImportLabelsHandler(
            new OfferRepository(
                new EventRepository($eventStore, $eventBus),
                new PlaceRepository($eventStore, $eventBus)
            ),
            new LabelImportPreProcessor(
                $this->labelService,
                $this->labelPermissionRepository,
                'b4ac44f4-31d0-4dcd-968e-c01538f117d8'
            )
        );
    }

    /**
     * @test
     */
    public function it_should_import_labels_and_also_record_label_added_events(): void
    {
        $this->labelService->expects($this->at(0))
            ->method('createLabelAggregateIfNew')
            ->with(new DeprecatedLabelName('foo'), true);

        $this->labelService->expects($this->at(1))
            ->method('createLabelAggregateIfNew')
            ->with(new DeprecatedLabelName('bar'), false);

        $this->labelPermissionRepository->expects($this->any())
            ->method('getByName')
            ->willReturnCallback(
                function (StringLiteral $labelName) {
                    return new Entity(
                        new UUID(\Ramsey\Uuid\Uuid::uuid4()->toString()),
                        $labelName,
                        $labelName->toNative() !== 'bar' ? Visibility::VISIBLE() : Visibility::INVISIBLE(),
                        Privacy::PRIVACY_PUBLIC()
                    );
                }
            );

        $id = '39007d2d-acec-438d-a687-f2d8400d4c1e';

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->eventCreated($id)])
            ->when(
                new ImportLabels(
                    $id,
                    new Labels(
                        new Label(
                            new LabelName('foo'),
                            true
                        ),
                        new Label(
                            new LabelName('bar'),
                            false
                        )
                    )
                )
            )
            ->then(
                [
                    new LabelsImported(
                        $id,
                        new Labels(
                            new Label(
                                new LabelName('foo'),
                                true
                            ),
                            new Label(
                                new LabelName('bar'),
                                false
                            )
                        )
                    ),
                    new LabelAdded($id, new DeprecatedLabel('foo', true)),
                    new LabelAdded($id, new DeprecatedLabel('bar', false)),
                ]
            );
    }

    /**
     * @test
     */
    public function it_does_not_add_a_private_label_if_not_allowed(): void
    {
        $id = '39007d2d-acec-438d-a687-f2d8400d4c1e';

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $this->eventCreated($id),
                ]
            )
            ->when(
                (new ImportLabels(
                    $id,
                    new Labels(new Label(new LabelName('not_allowed')))
                )
                )
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_should_not_remove_private_labels_that_are_already_on_the_offer_via_import(): void
    {
        $id = '39007d2d-acec-438d-a687-f2d8400d4c1e';

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $this->eventCreated($id),
                    new LabelsImported(
                        $id,
                        new Labels(
                            new Label(
                                new LabelName('not_allowed')
                            ),
                            new Label(
                                new LabelName('allowed')
                            )
                        )
                    ),
                    new LabelAdded($id, new DeprecatedLabel('not_allowed')),
                    new LabelAdded($id, new DeprecatedLabel('allowed')),
                ]
            )
            ->when(
                new ImportLabels($id, new Labels())
            )
            ->then([new LabelRemoved($id, new DeprecatedLabel('allowed'))]);
    }

    /**
     * @test
     */
    public function it_should_not_remove_labels_that_were_not_imported_before(): void
    {
        $this->labelService->expects($this->never())
            ->method('createLabelAggregateIfNew');

        $id = '39007d2d-acec-438d-a687-f2d8400d4c1e';

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $this->eventCreated($id),
                    new LabelAdded($id, new DeprecatedLabel('label 1')),
                    new LabelAdded($id, new DeprecatedLabel('label 2')),
                ]
            )
            ->when(new ImportLabels($id, new Labels()))
            ->then([]);
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
