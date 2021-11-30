<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use Broadway\EventHandling\SimpleEventBus;
use Broadway\EventStore\InMemoryEventStore;
use Broadway\EventStore\TraceableEventStore;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\OrganizerRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateOrganizerRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableEventStore $eventStore;

    private TraceableCommandBus $commandBus;

    /** @var IriGeneratorInterface|MockObject */
    private $iriGenerator;

    private CreateOrganizerRequestHandler $createOrganizerRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->eventStore = new TraceableEventStore(new InMemoryEventStore());
        $this->eventStore->trace();

        $organizerRepository = new OrganizerRepository(
            $this->eventStore,
            new SimpleEventBus()
        );

        $this->commandBus = new TraceableCommandBus();

        /** @var UuidGeneratorInterface|MockObject $uuidGenerator */
        $uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $uuidGenerator->method('generate')
            ->willReturn('6c583739-a848-41ab-b8a3-8f7dab6f8ee1');

        $this->createOrganizerRequestHandler = new CreateOrganizerRequestHandler(
            $organizerRepository,
            $this->commandBus,
            $uuidGenerator,
            new CallableIriGenerator(fn ($id) => 'https://io.uitdatabank.be/organizers/' . $id)
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    protected function tearDown(): void
    {
        $this->eventStore->clearEvents();
    }

    /**
     * @test
     */
    public function it_handles_creating_an_organizer_from_legacy_format(): void
    {
        $createOrganizerRequest = $this->psr7RequestBuilder
            ->withBodyFromArray([
                'mainLanguage' => 'nl',
                'name' => 'publiq',
                'website' => 'https://www.publiq.be',
            ])
            ->build('POST');

        $this->createOrganizerRequestHandler->handle($createOrganizerRequest);

        $this->assertEquals(
            [
                new OrganizerCreatedWithUniqueWebsite(
                    '6c583739-a848-41ab-b8a3-8f7dab6f8ee1',
                    'nl',
                    'https://www.publiq.be',
                    'publiq'
                ),
            ],
            $this->eventStore->getEvents()
        );

        $this->assertEquals(
            [],
            $this->commandBus->getRecordedCommands()
        );
    }
}
