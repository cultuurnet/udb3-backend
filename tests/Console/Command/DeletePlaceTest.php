<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateLocation;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Offer\Commands\DeleteOffer;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class DeletePlaceTest extends TestCase
{
    private TraceableCommandBus $commandBus;

    /** @var EventRelationsRepository&MockObject */
    private $eventRelationsRepository;
    /** @var DocumentRepository&MockObject */
    private $placeDocumentRepository;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->commandBus->record();

        $this->eventRelationsRepository = $this->createMock(EventRelationsRepository::class);
        $this->placeDocumentRepository = $this->createMock(DocumentRepository::class);

        $command = new DeletePlace(
            $this->commandBus,
            $this->eventRelationsRepository,
            $this->placeDocumentRepository
        );

        $application = new Application();
        $application->add($command);

        $command = $application->find('place:delete');
        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     */
    public function it_should_give_warning_when_missing_arguments(): void
    {
        $this->commandTester->execute([
            'place-uuid' => null,
            'canonical-uuid' => null,
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Missing argument, the correct syntax is', $output);
    }

    /**
     * @test
     */
    public function it_should_give_warning_when_the_place_does_not_exist(): void
    {
        $placeUuid = Uuid::uuid4()->toString();
        $canonicalUuid = Uuid::uuid4()->toString();
        $this->placeDocumentRepository
            ->method('fetch')
            ->willReturnCallback(function ($id) use ($placeUuid, $canonicalUuid) {
                if ($id === $canonicalUuid) {
                    return new JsonDocument($id, json_encode([]));
                }

                if ($id === $placeUuid) {
                    throw new DocumentDoesNotExist('Place does not exist');
                }
            });


        $this->commandTester->execute([
            'place-uuid' => $placeUuid,
            'canonical-uuid' => $canonicalUuid,
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Place does not exist', $output);
    }

    /**
     * @test
     */
    public function it_should_give_warning_when_canonical_does_not_exist(): void
    {
        $placeUuid = Uuid::uuid4()->toString();
        $canonicalUuid = Uuid::uuid4()->toString();
        $this->placeDocumentRepository
            ->method('fetch')
            ->with($canonicalUuid)
            ->willThrowException(new DocumentDoesNotExist('Place does not exist'));

        $this->commandTester->execute([
            'place-uuid' => $placeUuid,
            'canonical-uuid' => $canonicalUuid,
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Canonical place does not exist', $output);
    }

    /**
     * @test
     */
    public function it_should_execute_successfully_dispatched_commands(): void
    {
        $eventId1 = Uuid::uuid4()->toString();
        $eventId2 = Uuid::uuid4()->toString();
        $placeUuidToDelete = 'place-uuid';
        $canonicalUuid = new LocationId(Uuid::uuid4()->toString());

        $this->placeDocumentRepository
            ->method('fetch')
            ->willReturn(new JsonDocument(Uuid::uuid4()->toString(), json_encode([])));

        $this->eventRelationsRepository
            ->method('getEventsLocatedAtPlace')
            ->willReturn([$eventId1, $eventId2]);

        $this->commandTester->execute([
            'place-uuid' => $placeUuidToDelete,
            'canonical-uuid' => $canonicalUuid->toString(),
            '--force' => true,
        ]);

        $this->assertEquals([
            new UpdateLocation($eventId1, $canonicalUuid),
            new UpdateLocation($eventId2, $canonicalUuid),
            new DeleteOffer($placeUuidToDelete),
        ], $this->commandBus->getRecordedCommands());

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Dispatching UpdateLocation for event with id ' . $eventId1, $output);
        $this->assertStringContainsString('Dispatching UpdateLocation for event with id ' . $eventId2, $output);
        $this->assertStringContainsString('Dispatching DeleteOffer for place with id ' . $placeUuidToDelete, $output);
    }
}
