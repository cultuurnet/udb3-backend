<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use CultuurNet\UDB3\Place\Canonical\DBALDuplicatePlaceRepository;
use CultuurNet\UDB3\Place\Canonical\ImportDuplicatePlacesProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportDuplicatePlacesTest extends TestCase
{
    private DBALDuplicatePlaceRepository&MockObject $dbalDuplicatePlaceRepository;
    private ImportDuplicatePlacesProcessor&MockObject $importDuplicatePlacesProcessor;
    private InputInterface&MockObject $input;
    private OutputInterface&MockObject $output;
    private ImportDuplicatePlaces $command;

    protected function setUp(): void
    {
        $this->dbalDuplicatePlaceRepository = $this->createMock(DBALDuplicatePlaceRepository::class);
        $this->importDuplicatePlacesProcessor = $this->createMock(ImportDuplicatePlacesProcessor::class);
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);

        $this->command = new ImportDuplicatePlaces(
            $this->dbalDuplicatePlaceRepository,
            $this->importDuplicatePlacesProcessor
        );
    }

    public function testExecuteSucceedsWhenTablesAreAlreadySynced(): void
    {
        $this->dbalDuplicatePlaceRepository
            ->expects($this->once())
            ->method('howManyPlacesAreToBeImported')
            ->willReturn(0);

        $this->dbalDuplicatePlaceRepository
            ->expects($this->once())
            ->method('getPlacesNoLongerInCluster')
            ->willReturn([]);

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
            ->willReturn(50);

        $this->dbalDuplicatePlaceRepository
            ->expects($this->once())
            ->method('getPlacesNoLongerInCluster')
            ->willReturn([Uuid::uuid4()]);

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
