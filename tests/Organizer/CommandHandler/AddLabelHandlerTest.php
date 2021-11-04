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
use CultuurNet\UDB3\Label as LegacyLabel;
use CultuurNet\UDB3\Label\LabelServiceInterface;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\LabelName as LegacyLabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Organizer\Commands\AddLabel;
use CultuurNet\UDB3\Organizer\Events\LabelAdded;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\OrganizerRepository;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use ValueObjects\Geography\Country;
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
            new OrganizerRepository(
                $eventStore,
                $eventBus
            ),
            $labelRepository,
            $this->labelService
        );
    }

    /**
     * @test
     */
    public function it_handles_add_label(): void
    {
        $id = '5e360b25-fd85-4dac-acf4-0571e0b57dce';
        $label = new Label(new LabelName('foo'), true);

        $this->labelService
            ->method('createLabelAggregateIfNew')
            ->with(new LegacyLabelName('foo'), true);

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->organizerCreated($id)])
            ->when(new AddLabel($id, $label))
            ->then([new LabelAdded($id, new LegacyLabel('foo'))]);
    }

    /**
     * @test
     */
    public function it_handles_add_invisible_label(): void
    {
        $id = '5e360b25-fd85-4dac-acf4-0571e0b57dce';
        $label = new Label(new LabelName('bar'), false);

        $this->labelService
            ->method('createLabelAggregateIfNew')
            ->with(new LegacyLabelName('bar'), false);

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->organizerCreated($id)])
            ->when(new AddLabel($id, $label))
            ->then([new LabelAdded($id, new LegacyLabel('bar', false))]);
    }

    /**
     * @test
     */
    public function it_does_not_add_the_same_label_twice(): void
    {
        $id = '5e360b25-fd85-4dac-acf4-0571e0b57dce';
        $label = new Label(new LabelName('foo'), true);

        $this->labelService
            ->method('createLabelAggregateIfNew')
            ->with(new LegacyLabelName('foo'), true);

        $this->scenario
            ->withAggregateId($id)
            ->given([
                $this->organizerCreated($id),
                new LabelAdded($id, new LegacyLabel('foo')),
            ])
            ->when(new AddLabel($id, $label))
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
