<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Offer\Commands\UpdateFacilities;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Search\ResultsGeneratorInterface;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class RemoveFacilitiesFromPlace extends AbstractCommand
{
    private const FACILITY_QUERY = 'terms.id:3.';

    private ResultsGeneratorInterface $searchResultsGenerator;

    public function __construct(
        CommandBus $commandBus,
        SearchServiceInterface $searchService
    ) {
        $this->searchResultsGenerator = new ResultsGenerator(
            $searchService,
            ['created' => 'asc'],
            100
        );

        parent::__construct($commandBus);
    }

    public function configure(): void
    {
        $this->setName('place:facilities:remove');

        $this->setDescription(
            'Remove all facilities from either a single place or all places with `q=' . self::FACILITY_QUERY . '`'
        );

        $this->addArgument('id', InputArgument::OPTIONAL, 'Optional id of the place to remove all facilities');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $placeId = $input->getArgument('id');

        if ($placeId) {
            $count = 1;
        } else {
            $count = $this->searchResultsGenerator->count(self::FACILITY_QUERY);
        }

        if ($count === 0) {
            $output->writeln('Found no places to remove facilities');
            return 0;
        }

        if (!$this->askConfirmation($input, $output, $count)) {
            return 0;
        }

        if ($placeId) {
            $places = [$placeId];
        } else {
            $places = $this->searchResultsGenerator->search(self::FACILITY_QUERY);
        }

        foreach ($places as $place) {
            $output->writeln('Dispatching UpdateFacilities for ' . $place);
            $this->commandBus->dispatch(new UpdateFacilities($place, []));
        }

        return 0;
    }

    private function askConfirmation(InputInterface $input, OutputInterface $output, int $count): bool
    {
        return $this
            ->getHelper('question')
            ->ask(
                $input,
                $output,
                new ConfirmationQuestion(
                    "This action will remove the facilities from {$count} place(s), continue? [y/N] ",
                    false
                )
            );
    }
}
