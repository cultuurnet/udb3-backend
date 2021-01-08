<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Event\ValueObjects\StatusReason;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Commands\Status\UpdateStatus;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

abstract class AbstractUpdateOfferStatusCommand extends AbstractCommand
{
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $query = 'ADD_SAPI_QUERY_HERE';
        $status = new Status(
            StatusType::unavailable(),
            [
                new StatusReason(new Language('nl'), 'Afgelast door Covid-19'),
                new StatusReason(new Language('en'), 'Cancelled because of Covid-19'),
            ]
        );

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
            $this->commandBus->dispatch(
                new UpdateStatus($id, $status)
            );
        }

        $output->writeln("Updated {$count} offers");

        return 0;
    }
}
