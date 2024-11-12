<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use CultuurNet\UDB3\Offer\Commands\UpdateBookingAvailability;
use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailability;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use CultuurNet\UDB3\Search\Sorting;
use Exception;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class UpdateBookingAvailabilityCommand extends AbstractCommand
{
    private ResultsGenerator $searchResultsGenerator;

    public function __construct(CommandBus $commandBus, SearchServiceInterface $searchService)
    {
        $this->searchResultsGenerator = new ResultsGenerator(
            $searchService,
            new Sorting('created', 'asc'),
            100
        );

        $this->setName('event:booking-availability:update');
        $this->setDescription('Update the booking availability on a batch of events resulting from a SAPI 3 query.');

        parent::__construct($commandBus);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sapiQuestion = new Question(
            'Provide SAPI query for events to update (periodic and permanent events are excluded by default)' . PHP_EOL
        );
        $sapiQuery = '(' . $this->getHelper('question')->ask($input, $output, $sapiQuestion) . ')';
        $sapiQuery .= ' AND (calendarType:single OR calendarType:multiple)';

        $output->writeln('');

        $count = $this->searchResultsGenerator->count($sapiQuery);
        if ($count <= 0) {
            $output->writeln('Query found 0 events to update');
            return 0;
        }

        $bookingQuestion =  new ChoiceQuestion(
            'Provide the new booking availability?',
            [
                BookingAvailabilityType::Available()->toString(),
                BookingAvailabilityType::Unavailable()->toString(),
            ]
        );
        $bookingQuestion->setErrorMessage('Invalid booking availability: %s');
        $bookingAvailability = new BookingAvailabilityType(
            $this->getHelper('question')->ask($input, $output, $bookingQuestion)
        );

        $output->writeln('');

        $confirmation = $this->getHelper('question')->ask(
            $input,
            $output,
            new ConfirmationQuestion(
                "This action will update the status of $count events to {$bookingAvailability->toString()}, continue? [y/N] ",
                false
            )
        );
        if (!$confirmation) {
            return 0;
        }

        $exceptions = [];
        $offers = $this->searchResultsGenerator->search($sapiQuery);
        $progressBar = new ProgressBar($output, $count);

        foreach ($offers as $id => $offer) {
            try {
                $this->commandBus->dispatch(
                    new UpdateBookingAvailability($id, new BookingAvailability($bookingAvailability))
                );
            } catch (Exception $exception) {
                $exceptions[$id] = 'Event with id: ' . $id . ' caused an exception: ' . $exception->getMessage();
            }

            $progressBar->advance();
        }
        $progressBar->finish();

        $output->writeln('');
        foreach ($exceptions as $exception) {
            $output->writeln($exception);
        }

        return 0;
    }
}
