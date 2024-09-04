<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use CultuurNet\UDB3\Place\Canonical\ClustersDiffResult;
use CultuurNet\UDB3\Place\Canonical\DBALDuplicatePlaceRepository;
use CultuurNet\UDB3\Place\Canonical\ImportDuplicatePlacesProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportDuplicatePlacesTest extends TestCase
{
    /** @var DBALDuplicatePlaceRepository|MockObject  */
    private $dbalDuplicatePlaceRepository;
    /** @var ImportDuplicatePlacesProcessor|MockObject  */
    private $importDuplicatePlacesProcessor;
    /** @var LoggerInterface|MockObject  */
    private $logger;
    /** @var InputInterface|MockObject  */
    private $input;
    /** @var OutputInterface|MockObject  */
    private $output;
    private ImportDuplicatePlaces $command;

    protected function setUp(): void
    {
        $this->dbalDuplicatePlaceRepository = $this->createMock(DBALDuplicatePlaceRepository::class);
        $this->importDuplicatePlacesProcessor = $this->createMock(ImportDuplicatePlacesProcessor::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);

        $this->command = new ImportDuplicatePlaces(
            $this->dbalDuplicatePlaceRepository,
            $this->importDuplicatePlacesProcessor,
            $this->logger
        );
    }

    public function testExecuteFailsWhenImportTableIsEmpty(): void
    {
        $this->dbalDuplicatePlaceRepository
            ->expects($this->once())
            ->method('howManyPlacesAreToBeImported')
            ->willReturn(0);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Import duplicate places failed. Duplicate_places_import table is empty.');

        $this->output
            ->expects($this->once())
            ->method('writeln')
            ->with('<error>Import duplicate places failed. Duplicate_places_import table is empty.</error>');

        $this->assertEquals(1, $this->command->run($this->input, $this->output));
    }

    public function testExecuteSucceedsWhenTablesAreAlreadySynced(): void
    {
        $this->dbalDuplicatePlaceRepository
            ->expects($this->once())
            ->method('howManyPlacesAreToBeImported')
            ->willReturn(10);

        $this->dbalDuplicatePlaceRepository
            ->expects($this->once())
            ->method('calculateHowManyClustersHaveChanged')
            ->willReturn(new ClustersDiffResult(0, 0));

        $this->output
            ->expects($this->once())
            ->method('writeln')
            ->with('duplicate_places is already synced');

        $this->assertEquals(0, $this->command->run($this->input, $this->output));
    }

    public function testExecuteConfirmsAndSyncsWhenChangesAreWithinLimits(): void
    {
        $this->dbalDuplicatePlaceRepository
            ->expects($this->once())
            ->method('howManyPlacesAreToBeImported')
            ->willReturn(10);

        $this->dbalDuplicatePlaceRepository
            ->expects($this->once())
            ->method('calculateHowManyClustersHaveChanged')
            ->willReturn(new ClustersDiffResult(50, 30));

        $helper = $this->createMock(QuestionHelper::class);
        $helper->expects($this->once())
            ->method('ask')
            ->willReturn(true);
        $this->command->setHelperSet(new HelperSet(['question' => $helper]));

        $this->importDuplicatePlacesProcessor
            ->expects($this->once())
            ->method('sync');

        $this->assertEquals(0, $this->command->run($this->input, $this->output));
    }
}
