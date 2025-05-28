<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Label\LabelImportPreProcessor;
use CultuurNet\UDB3\Label\LabelServiceInterface;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Organizer\Commands\ReplaceLabels;
use CultuurNet\UDB3\Organizer\Events\LabelAdded;
use CultuurNet\UDB3\Organizer\Events\LabelRemoved;
use CultuurNet\UDB3\Organizer\Events\LabelsReplaced;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\OrganizerRepository;
use PHPUnit\Framework\MockObject\MockObject;

class ReplaceLabelsHandlerTest extends CommandHandlerScenarioTestCase
{
    private MockObject $labelService;

    private MockObject $labelPermissionRepository;

    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): ReplaceLabelsHandler
    {
        $this->labelService = $this->createMock(LabelServiceInterface::class);

        $this->labelPermissionRepository = $this->createMock(ReadRepositoryInterface::class);
        $this->labelPermissionRepository->expects($this->any())
            ->method('canUseLabel')
            ->willReturnCallback(
                function (string $userId, string $labelName) {
                    return $labelName !== 'not_allowed';
                }
            );

        return new ReplaceLabelsHandler(
            new OrganizerRepository($eventStore, $eventBus),
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
    public function it_replaces_labels(): void
    {
        $this->labelService->expects($this->exactly(2))
            ->method('createLabelAggregateIfNew')
            ->withConsecutive(
                [
                    new LabelName('foo'),
                    true,
                ],
                [
                    new LabelName('bar'),
                    false,
                ]
            );

        $this->labelPermissionRepository->expects($this->any())
            ->method('getByName')
            ->willReturnCallback(
                function ($labelName) {
                    return new Entity(
                        Uuid::uuid4(),
                        $labelName,
                        $labelName !== 'bar' ? Visibility::visible() : Visibility::invisible(),
                        Privacy::public()
                    );
                }
            );

        $id = '39007d2d-acec-438d-a687-f2d8400d4c1e';

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->organizerCreated($id)])
            ->when(
                new ReplaceLabels(
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
                    new LabelsReplaced(
                        $id,
                        ['foo'],
                        ['bar']
                    ),
                    new LabelAdded($id, 'foo', true),
                    new LabelAdded($id, 'bar', false),
                ]
            );
    }

    /**
     * @test
     */
    public function it_keeps_private_labels(): void
    {
        $id = '39007d2d-acec-438d-a687-f2d8400d4c1e';

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $this->organizerCreated($id),
                    new LabelsReplaced(
                        $id,
                        [
                            'not_allowed',
                            'allowed',
                        ],
                        []
                    ),
                    new LabelAdded($id, 'not_allowed'),
                    new LabelAdded($id, 'allowed'),
                ]
            )
            ->when(
                new ReplaceLabels($id, new Labels())
            )
            ->then([
                new LabelsReplaced($id, [], []),
                new LabelRemoved($id, 'allowed'),
            ]);
    }

    /**
     * @test
     */
    public function it_removes_manual_labels(): void
    {
        $this->labelService->expects($this->never())
            ->method('createLabelAggregateIfNew');

        $id = '39007d2d-acec-438d-a687-f2d8400d4c1e';

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $this->organizerCreated($id),
                    new LabelAdded($id, 'label 1'),
                    new LabelAdded($id, 'label 2'),
                ]
            )
            ->when(new ReplaceLabels($id, new Labels()))
            ->then([
                new LabelsReplaced($id, [], []),
                new LabelRemoved($id, 'label 1'),
                new LabelRemoved($id, 'label 2'),
            ]);
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
            ['url']
        );
    }
}
