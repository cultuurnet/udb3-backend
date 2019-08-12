<?php

namespace CultuurNet\UDB3\UiTPAS\Event;

use Broadway\CommandHandling\CommandBusInterface;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Event\Commands\AddLabel;
use CultuurNet\UDB3\Event\Commands\RemoveLabel;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\UiTPAS\CardSystem\CardSystem;
use CultuurNet\UDB3\UiTPAS\CardSystem\CardSystems;
use CultuurNet\UDB3\UiTPAS\Event\Event\EventCardSystemsUpdated;
use CultuurNet\UDB3\UiTPAS\Label\UiTPASLabelsRepository;
use CultuurNet\UDB3\UiTPAS\ValueObject\Id;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ValueObjects\StringLiteral\StringLiteral;

class EventProcessManagerTest extends TestCase
{
    /**
     * @var CommandBusInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $commandBus;

    /**
     * @var UiTPASLabelsRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $uitpasLabelsRepository;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var EventProcessManager
     */
    private $eventProcessManager;

    /**
     * @var Label[]
     */
    private $uitpasLabels;

    /**
     * @var object[]
     */
    private $tracedCommands;

    /**
     * @var array
     */
    private $warningLogs;

    /**
     * @var array
     */
    private $infoLogs;

    public function setUp()
    {
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->uitpasLabelsRepository = $this->createMock(UiTPASLabelsRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->eventProcessManager = new EventProcessManager(
            $this->commandBus,
            $this->uitpasLabelsRepository,
            $this->logger
        );

        $this->uitpasLabels = [
            'c73d78b7-95a7-45b3-bde5-5b2ec7b13afa' => new Label('Paspartoe'),
            'ebd91df0-8ed7-4522-8401-ef5508ad1426' => new Label('UiTPAS'),
            'f23ccb75-190a-4814-945e-c95e83101cc5' => new Label('UiTPAS Gent'),
            '98ce6fbc-fb68-4efc-b8c7-95763cb967dd' => new Label('UiTPAS Oostende'),
            '68f849c0-bf55-4f73-b0f4-e0683bf0c807' => new Label('UiTPAS regio Aalst'),
            'cd6200cc-5b9d-43fd-9638-f6cc27f1c9b8' => new Label('UiTPAS Dender'),
            'd9cf96b6-1256-4760-b66b-1c31152d7db4' => new Label('UiTPAS Zuidwest'),
            'aaf3a58e-2aac-45b3-a9e9-3f3ebf467681' => new Label('UiTPAS Mechelen'),
            '47256d4c-47e8-4046-b9bb-acb166920f76' => new Label('UiTPAS Kempen'),
            '54b5273e-5e0b-4c1e-b33f-93eca55eb472' => new Label('UiTPAS Maasmechelen'),
        ];

        $this->uitpasLabelsRepository->expects($this->any())
            ->method('loadAll')
            ->willReturn($this->uitpasLabels);

        $this->tracedCommands = [];

        $this->commandBus->expects($this->any())
            ->method('dispatch')
            ->willReturnCallback(
                function ($command) {
                    $this->tracedCommands[] = $command;
                }
            );

        $this->logger->expects($this->any())
            ->method('warning')
            ->willReturnCallback(
                function ($msg) {
                    $this->warningLogs[] = $msg;
                }
            );

        $this->logger->expects($this->any())
            ->method('info')
            ->willReturnCallback(
                function ($msg) {
                    $this->infoLogs[] = $msg;
                }
            );
    }

    /**
     * @test
     */
    public function it_should_remove_every_uitpas_label_from_an_event_if_it_has_no_card_systems_after_an_update()
    {
        $eventId = new Id('cbee7413-ac1e-4dfb-8004-34767eafb8b7');
        $cardSystems = new CardSystems();

        $cardSystemsUpdated = new EventCardSystemsUpdated($eventId, $cardSystems);

        $domainMessage = DomainMessage::recordNow(
            'cbee7413-ac1e-4dfb-8004-34767eafb8b7',
            7,
            new Metadata([]),
            $cardSystemsUpdated
        );

        $expectedCommands = [
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', new Label('Paspartoe')),
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', new Label('UiTPAS')),
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', new Label('UiTPAS Gent')),
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', new Label('UiTPAS Oostende')),
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', new Label('UiTPAS regio Aalst')),
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', new Label('UiTPAS Dender')),
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', new Label('UiTPAS Zuidwest')),
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', new Label('UiTPAS Mechelen')),
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', new Label('UiTPAS Kempen')),
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', new Label('UiTPAS Maasmechelen')),
        ];

        $this->eventProcessManager->handle($domainMessage);

        $this->assertEquals($expectedCommands, $this->tracedCommands);
    }

    /**
     * @test
     */
    public function it_should_add_uitpas_labels_for_active_card_systems_to_an_updated_event_with_card_systems()
    {
        $eventId = new Id('cbee7413-ac1e-4dfb-8004-34767eafb8b7');
        $cardSystems = (new CardSystems())
            ->withKey(
                'c73d78b7-95a7-45b3-bde5-5b2ec7b13afa',
                new CardSystem(
                    new Id('c73d78b7-95a7-45b3-bde5-5b2ec7b13afa'),
                    new StringLiteral('Mock CS Paspartoe')
                )
            )
            ->withKey(
                'f23ccb75-190a-4814-945e-c95e83101cc5',
                new CardSystem(
                    new Id('f23ccb75-190a-4814-945e-c95e83101cc5'),
                    new StringLiteral('Mock CS UiTPAS Gent')
                )
            )
            ->withKey(
                '98ce6fbc-fb68-4efc-b8c7-95763cb967dd',
                new CardSystem(
                    new Id('98ce6fbc-fb68-4efc-b8c7-95763cb967dd'),
                    new StringLiteral('Mock CS UiTPAS Oostende')
                )
            );

        $cardSystemsUpdated = new EventCardSystemsUpdated($eventId, $cardSystems);

        $domainMessage = DomainMessage::recordNow(
            $eventId->toNative(),
            8,
            new Metadata([]),
            $cardSystemsUpdated
        );

        $expectedCommands = [
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', new Label('UiTPAS')),
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', new Label('UiTPAS regio Aalst')),
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', new Label('UiTPAS Dender')),
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', new Label('UiTPAS Zuidwest')),
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', new Label('UiTPAS Mechelen')),
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', new Label('UiTPAS Kempen')),
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', new Label('UiTPAS Maasmechelen')),
            new AddLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', new Label('Paspartoe', true)),
            new AddLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', new Label('UiTPAS Gent', true)),
            new AddLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', new Label('UiTPAS Oostende', true)),
        ];

        $this->eventProcessManager->handle($domainMessage);

        $this->assertEquals($expectedCommands, $this->tracedCommands);
    }

    /**
     * @test
     */
    public function it_should_log_a_warning_if_no_label_can_be_found_for_an_active_card_system()
    {
        $eventId = new Id('cbee7413-ac1e-4dfb-8004-34767eafb8b7');
        $cardSystems = (new CardSystems())
            ->withKey(7, new CardSystem(new Id('7'), new StringLiteral('Mock CS')));

        $cardSystemsUpdated = new EventCardSystemsUpdated($eventId, $cardSystems);

        $domainMessage = DomainMessage::recordNow(
            $eventId->toNative(),
            8,
            new Metadata([]),
            $cardSystemsUpdated
        );

        $this->eventProcessManager->handle($domainMessage);

        $this->assertContains(
            'Handling updated card systems message for event cbee7413-ac1e-4dfb-8004-34767eafb8b7',
            $this->infoLogs
        );

        $this->assertContains(
            'Could not find UiTPAS label for card system 7',
            $this->warningLogs
        );
    }
}
