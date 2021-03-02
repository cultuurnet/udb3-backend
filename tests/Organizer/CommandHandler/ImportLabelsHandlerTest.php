<?php

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Label\LabelServiceInterface;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label as Udb3ModelLabel;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName as Udb3ModelLabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels as Udb3ModelLabels;
use CultuurNet\UDB3\Organizer\Commands\ImportLabels;
use CultuurNet\UDB3\Organizer\Events\LabelAdded;
use CultuurNet\UDB3\Organizer\Events\LabelRemoved;
use CultuurNet\UDB3\Organizer\Events\LabelsImported;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\OrganizerRepository;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use ValueObjects\Geography\Country;
use ValueObjects\Identity\UUID;

final class ImportLabelsHandlerTest extends CommandHandlerScenarioTestCase
{
    /**
     * @var LabelServiceInterface|MockObject
     */
    private $labelService;

    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): ImportLabelsHandler
    {
        $this->labelService = $this->createMock(LabelServiceInterface::class);

        return new ImportLabelsHandler(
            new OrganizerRepository(
                $eventStore,
                $eventBus
            ),
            $this->labelService
        );
    }

    /**
     * @test
     */
    public function it_handles_label_imports(): void
    {
        $this->labelService->expects($this->at(0))
            ->method('createLabelAggregateIfNew')
            ->with(new LabelName('foo'), true);

        $this->labelService->expects($this->at(1))
            ->method('createLabelAggregateIfNew')
            ->with(new LabelName('bar'), true);

        $id = '86a51894-e18e-4a6a-b7c5-d774e8c81074';

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $this->organizerCreated($id),
                ]
            )
            ->when(
                new ImportLabels(
                    $id,
                    new Udb3ModelLabels(
                        new Udb3ModelLabel(
                            new Udb3ModelLabelName('foo'),
                            true
                        ),
                        new Udb3ModelLabel(
                            new Udb3ModelLabelName('bar'),
                            true
                        )
                    )
                )
            )
            ->then(
                [
                    new LabelsImported(
                        $id,
                        new Udb3ModelLabels(
                            new Udb3ModelLabel(
                                new Udb3ModelLabelName('foo'),
                                true
                            ),
                            new Udb3ModelLabel(
                                new Udb3ModelLabelName('bar'),
                                true
                            )
                        )
                    ),
                    new LabelAdded($id, new Label('foo')),
                    new LabelAdded($id, new Label('bar')),
                ]
            );
    }

    /**
     * @test
     */
    public function it_will_not_replace_private_labels_that_are_already_on_the_organizer(): void
    {
        $this->labelService
            ->method('createLabelAggregateIfNew')
            ->with(new LabelName('existing_private'), true);

        $id = '86a51894-e18e-4a6a-b7c5-d774e8c81074';

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $this->organizerCreated($id),
                    new LabelAdded($id, new Label('existing_to_be_removed')),
                    new LabelAdded($id, new Label('existing_private')),
                ]
            )
            ->when(
                (new ImportLabels($id, new Udb3ModelLabels()))
                    ->withLabelsToKeepIfAlreadyOnOrganizer(
                        new Udb3ModelLabels(
                            new Udb3ModelLabel(
                                new Udb3ModelLabelName('existing_private')
                            )
                        )
                    )
            )
            ->then(
                [
                    new LabelRemoved($id, new Label('existing_to_be_removed')),
                ]
            );
    }

    private function organizerCreated($id): OrganizerCreated
    {
        return new OrganizerCreated(
            UUID::fromNative($id),
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
