<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateLocation;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifier;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifiers;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Search\Results;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class MoveEventsTest extends TestCase
{
    private TraceableCommandBus $commandBus;

    /**
     * @var SearchServiceInterface|(SearchServiceInterface&object&MockObject)|(SearchServiceInterface&MockObject)|(object&MockObject)|MockObject
     */
    private $searchService;

    private MoveEvents $moveEvents;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->commandBus->record();

        $this->searchService = $this->createMock(SearchServiceInterface::class);
        $this->moveEvents = new MoveEvents($this->commandBus, $this->searchService);

        $questionHelper = $this->createMock(QuestionHelper::class);
        $questionHelper
            ->method('ask')
            ->willReturn(true);
        $this->moveEvents->setHelperSet(new HelperSet(['question' => $questionHelper]));
    }

    /**
     * @dataProvider executeProvider
     */
    public function test_execute(array $foundEvents, int $expectedReturnCode): void
    {
        $input = new ArrayInput([
            'place-uuid' => '764be3c2-bc3a-4525-bf0a-eb3d3b6cc9e9',
            'query' => 'location.id:336ac1eb-aca5-4b9f-8cb5-6ebd71445307',
        ]);

        $output = new BufferedOutput();

        $this->searchService
            ->method('search')
            ->with('location.id:336ac1eb-aca5-4b9f-8cb5-6ebd71445307')
            ->willReturn($this->convertToResults($foundEvents));

        $returnCode = $this->moveEvents->run($input, $output);

        $this->assertEquals($expectedReturnCode, $returnCode);

        $locs = array_map(function ($eventId) {
            return new UpdateLocation($eventId, new LocationId('764be3c2-bc3a-4525-bf0a-eb3d3b6cc9e9'));
        }, $foundEvents);

        $this->assertEquals($locs, $this->commandBus->getRecordedCommands());
    }

    public function executeProvider(): array
    {
        return [
            'successful move' => [
                'events' => ['3c3f714f-4695-4237-87c5-780d0e599267', '2719c5e4-71ab-4ced-ad39-26999e795bef'],
                'expectedReturnCode' => 0,
            ],
            'no events found' => [
                'events' => [],
                'expectedReturnCode' => 1,
            ],
        ];
    }

    private function convertToResults(array $events): Results
    {
        $events = array_map(function ($eventId) {
            return new ItemIdentifier(
                new Url('https://io.uitdatabank.dev/event/' . $eventId),
                $eventId,
                ItemType::event()
            );
        }, $events);

        return new Results(
            new ItemIdentifiers(...$events),
            count($events)
        );
    }
}
