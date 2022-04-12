<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBus;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Place\CannotMarkPlaceAsCanonical;
use CultuurNet\UDB3\Place\CannotMarkPlaceAsDuplicate;
use CultuurNet\UDB3\Place\Canonical\CanonicalService;
use CultuurNet\UDB3\Place\Canonical\DuplicatePlaceRepository;
use CultuurNet\UDB3\Place\Commands\MarkAsDuplicate;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class MarkPlacesAsDuplicateFromTableCommand extends AbstractCommand
{
    private EventListener $processManager;

    private DuplicatePlaceRepository $duplicatePlaceRepository;

    private CanonicalService $canonicalService;

    public function __construct(
        CommandBus $commandBus,
        EventListener $processManager,
        DuplicatePlaceRepository $duplicatePlaceRepository,
        CanonicalService $canonicalService
    ) {
        parent::__construct($commandBus);
        $this->processManager = $processManager;
        $this->duplicatePlaceRepository = $duplicatePlaceRepository;
        $this->canonicalService = $canonicalService;
    }

    public function configure(): void
    {
        $this->setName('place:mark-as-duplicate-bulk');
        $this->setDescription('Marks multiple Places as duplicate of another one, based on the autofilled table');
        $this->addOption(
            'force',
            null,
            InputOption::VALUE_NONE,
            'Skip confirmation.'
        );
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Execute the mark-places-as-duplicate as a dry run.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $logger = new ConsoleLogger($output);
        $dryRun = (bool) $input->getOption('dry-run');

        $clusterIds = $this->duplicatePlaceRepository->getClusterIds();

        if (!$this->askConfirmation($input, $output, count($clusterIds))) {
            return 0;
        }

        foreach ($clusterIds as $clusterId) {
            $cluster = $this->duplicatePlaceRepository->getCluster($clusterId);
            $canonicalId = $this->canonicalService->getCanonical($cluster);
            $duplicateIds = array_diff($cluster, [$canonicalId]);

            foreach ($duplicateIds as $duplicateId) {
                if ($dryRun) {
                    $logger->info('Would mark place' . $duplicateId . ' as duplicate of' . $canonicalId);
                    continue;
                }
                try {
                    $this->commandBus->dispatch(
                        new MarkAsDuplicate(
                            $duplicateId,
                            $canonicalId
                        )
                    );
                    $logger->info('Successfully marked place' . $duplicateId . ' as duplicate of' . $canonicalId);
                } catch (CannotMarkPlaceAsCanonical | CannotMarkPlaceAsDuplicate $e) {
                    $logger->error($e->getMessage());
                    return 1;
                }
            }
        }
        return 0;
    }

    private function askConfirmation(InputInterface $input, OutputInterface $output, int $count): bool
    {
        if ($input->getOption('force')) {
            return true;
        }

        return $this
            ->getHelper('question')
            ->ask(
                $input,
                $output,
                new ConfirmationQuestion(
                    "This action will process {$count} clusters, continue? [y/N] ",
                    true
                )
            );
    }
}
