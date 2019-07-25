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
use CultuurNet\UDB3\UiTPAS\Label\UiTPASLabelsRepositoryInterface;
use CultuurNet\UDB3\UiTPAS\ValueObject\Id;
use Psr\Log\LoggerInterface;
use ValueObjects\StringLiteral\StringLiteral;

class EventProcessManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DocumentRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventDocumentRepository;

    /**
     * @var CommandBusInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $commandBus;

    /**
     * @var UiTPASLabelsRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
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
    private $errorLogs;

    /**
     * @var array
     */
    private $infoLogs;

    public function setUp()
    {
        $this->eventDocumentRepository = $this->createMock(DocumentRepositoryInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->uitpasLabelsRepository = $this->createMock(UiTPASLabelsRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->eventProcessManager = new EventProcessManager(
            $this->eventDocumentRepository,
            $this->commandBus,
            $this->uitpasLabelsRepository,
            $this->logger
        );

        $this->uitpasLabels = [
            new Label('Paspartoe'),
            new Label('UiTPAS'),
            new Label('UiTPAS Gent'),
            new Label('UiTPAS Oostende'),
            new Label('UiTPAS regio Aalst'),
            new Label('UiTPAS Dender'),
            new Label('UiTPAS Zuidwest'),
            new Label('UiTPAS Mechelen'),
            new Label('UiTPAS Kempen'),
            new Label('UiTPAS Maasmechelen'),
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
            ->method('error')
            ->willReturnCallback(
                function ($msg) {
                    $this->errorLogs[] = $msg;
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

        $expectedCommands = array_map(
            function (Label $label) use ($eventId) {
                return new RemoveLabel(
                    $eventId->toNative(),
                    $label
                );
            },
            $this->uitpasLabels
        );

        $this->eventProcessManager->handle($domainMessage);

        $actualCommands = $this->tracedCommands;

        // Check the count manually just in case both our $actualCommands and
        // $expectedCommands would have the wrong count.
        $this->assertCount(10, $actualCommands);
        $this->assertEquals($expectedCommands, $actualCommands);
    }

    /**
     * @test
     */
    public function it_should_copy_visible_organizer_uitpas_labels_to_an_updated_event_with_card_systems()
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

        $eventLd = json_encode(
            [
                '@id' => 'http://udb3.dev/event/cbee7413-ac1e-4dfb-8004-34767eafb8b7',
                '@type' => 'Event',
                'organizer' => [
                    'labels' => [
                        'Foo',
                        'Paspartoe',
                        'Bar',
                        'UiTPAS Oostende',
                    ],
                ],
            ]
        );

        $eventDocument = new JsonDocument($eventId->toNative(), $eventLd);

        $this->eventDocumentRepository->expects($this->once())
            ->method('get')
            ->with($eventDocument->getId())
            ->willReturn($eventDocument);

        $expectedCommands = [
            new RemoveLabel($eventId->toNative(), new Label('Paspartoe')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS Gent')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS Oostende')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS regio Aalst')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS Dender')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS Zuidwest')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS Mechelen')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS Kempen')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS Maasmechelen')),
            new AddLabel($eventId->toNative(), new Label('Paspartoe', true)),
            new AddLabel($eventId->toNative(), new Label('UiTPAS Oostende', true)),
        ];

        $this->eventProcessManager->handle($domainMessage);

        $this->assertEquals($expectedCommands, $this->tracedCommands);
    }

    /**
     * @test
     */
    public function it_should_copy_hidden_organizer_uitpas_labels_to_an_updated_event_with_card_systems()
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

        $eventLd = json_encode(
            [
                '@id' => 'http://udb3.dev/event/cbee7413-ac1e-4dfb-8004-34767eafb8b7',
                '@type' => 'Event',
                'organizer' => [
                    'hiddenLabels' => [
                        'Foo',
                        'Paspartoe',
                        'Bar',
                        'UiTPAS Oostende',
                    ],
                ],
            ]
        );

        $eventDocument = new JsonDocument($eventId->toNative(), $eventLd);

        $this->eventDocumentRepository->expects($this->once())
            ->method('get')
            ->with($eventDocument->getId())
            ->willReturn($eventDocument);

        $expectedCommands = [
            new RemoveLabel($eventId->toNative(), new Label('Paspartoe')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS Gent')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS Oostende')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS regio Aalst')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS Dender')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS Zuidwest')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS Mechelen')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS Kempen')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS Maasmechelen')),
            new AddLabel($eventId->toNative(), new Label('Paspartoe', false)),
            new AddLabel($eventId->toNative(), new Label('UiTPAS Oostende', false)),
        ];

        $this->eventProcessManager->handle($domainMessage);

        $this->assertEquals($expectedCommands, $this->tracedCommands);
    }

    /**
     * @test
     */
    public function it_should_copy_visible_and_hidden_organizer_uitpas_labels_to_an_updated_event_with_card_systems()
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

        $eventLd = json_encode(
            [
                '@id' => 'http://udb3.dev/event/cbee7413-ac1e-4dfb-8004-34767eafb8b7',
                '@type' => 'Event',
                'organizer' => [
                    'labels' => [
                        'Foo',
                        'Paspartoe',
                    ],
                    'hiddenLabels' => [
                        'Bar',
                        'UiTPAS Oostende',
                    ],
                ],
            ]
        );

        $eventDocument = new JsonDocument($eventId->toNative(), $eventLd);

        $this->eventDocumentRepository->expects($this->once())
            ->method('get')
            ->with($eventDocument->getId())
            ->willReturn($eventDocument);

        $expectedCommands = [
            new RemoveLabel($eventId->toNative(), new Label('Paspartoe')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS Gent')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS Oostende')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS regio Aalst')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS Dender')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS Zuidwest')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS Mechelen')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS Kempen')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS Maasmechelen')),
            new AddLabel($eventId->toNative(), new Label('Paspartoe', true)),
            new AddLabel($eventId->toNative(), new Label('UiTPAS Oostende', false)),
        ];

        $this->eventProcessManager->handle($domainMessage);

        $this->assertEquals($expectedCommands, $this->tracedCommands);
    }

    /**
     * @test
     */
    public function it_should_copy_no_labels_if_the_event_organizer_has_no_uitpas_labels()
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

        $eventLd = json_encode(
            [
                '@id' => 'http://udb3.dev/event/cbee7413-ac1e-4dfb-8004-34767eafb8b7',
                '@type' => 'Event',
                'organizer' => [
                    'labels' => [
                        'Foo',
                        'Bar',
                    ],
                ],
            ]
        );

        $eventDocument = new JsonDocument($eventId->toNative(), $eventLd);

        $this->eventDocumentRepository->expects($this->once())
            ->method('get')
            ->with($eventDocument->getId())
            ->willReturn($eventDocument);

        $expectedCommands = [
            new RemoveLabel($eventId->toNative(), new Label('Paspartoe')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS Gent')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS Oostende')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS regio Aalst')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS Dender')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS Zuidwest')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS Mechelen')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS Kempen')),
            new RemoveLabel($eventId->toNative(), new Label('UiTPAS Maasmechelen')),
        ];

        $this->eventProcessManager->handle($domainMessage);

        $this->assertEquals($expectedCommands, $this->tracedCommands);
    }

    /**
     * @test
     */
    public function it_should_log_an_error_if_no_organizer_can_be_found_for_an_event()
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

        $eventLd = json_encode(
            [
                '@id' => 'http://udb3.dev/event/cbee7413-ac1e-4dfb-8004-34767eafb8b7',
                '@type' => 'Event',
            ]
        );

        $eventDocument = new JsonDocument($eventId->toNative(), $eventLd);

        $this->eventDocumentRepository->expects($this->once())
            ->method('get')
            ->with($eventDocument->getId())
            ->willReturn($eventDocument);

        $this->eventProcessManager->handle($domainMessage);

        $this->assertContains(
            'Found no organizer on event ' . $eventId->toNative(),
            $this->errorLogs
        );
    }

    /**
     * @test
     */
    public function it_should_log_an_error_if_no_event_json_ld_can_be_found()
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

        $this->eventDocumentRepository->expects($this->once())
            ->method('get')
            ->with($eventId->toNative())
            ->willReturn(null);

        $this->eventProcessManager->handle($domainMessage);

        $this->assertContains(
            'Event with id ' . $eventId->toNative() . ' not found in injected DocumentRepository!',
            $this->errorLogs
        );
    }
}
