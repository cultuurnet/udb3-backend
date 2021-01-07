<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class UpdateOfferStatusCommand extends AbstractCommand
{
    private const QUERY_ARGUMENT = 'query';

    /**
     * @var ResultsGenerator
     */
    private $searchResultsGenerator;

    public function __construct(
        CommandBusInterface $commandBus,
        SearchServiceInterface $searchService
    ) {
        parent::__construct($commandBus);
        $this->commandBus = $commandBus;
        $this->searchResultsGenerator = new ResultsGenerator(
            $searchService,
            ['created' => 'asc'],
            100
        );
    }

    protected function configure()
    {
        $this->addArgument(
            self::QUERY_ARGUMENT,
            InputArgument::REQUIRED,
            'SAPI3 query to retrieve the offers to update'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $query = $input->getArgument(self::QUERY_ARGUMENT);
        $count = $this->searchResultsGenerator->count($query);

        if ($count === 0) {
            $output->writeln("Could not find any offers for this query.");
            return 0;
        }

        $confirmation = $this->getHelper('question')
            ->ask(
                $input,
                $output,
                new ConfirmationQuestion(
                    "This action will update the status of {$count} offers, continue? [y/N] ",
                    true
                )
            );

        if (!$confirmation) {
            return 0;
        }

        $offers = $this->searchResultsGenerator->search($query);
        foreach ($offers as $id => $offer) {
            // update status
        }

        return 0;
    }
}
