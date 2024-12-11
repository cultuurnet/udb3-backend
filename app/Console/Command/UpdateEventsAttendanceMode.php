<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateAttendanceMode;
use CultuurNet\UDB3\Event\Commands\UpdateLocation;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Online\AttendanceMode;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use CultuurNet\UDB3\Search\Sorting;
use Exception;
use RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

final class UpdateEventsAttendanceMode extends AbstractCommand
{
    private ResultsGenerator $searchResultsGenerator;

    public function __construct(CommandBus $commandBus, SearchServiceInterface $searchService)
    {
        $this->searchResultsGenerator = new ResultsGenerator(
            $searchService,
            new Sorting('created', 'asc'),
            100
        );

        parent::__construct($commandBus);
    }

    public function configure(): void
    {
        parent::configure();
        $this
            ->setName('event:attendanceMode:update')
            ->setDescription('Batch update the attendance mode of events based on a SAPI 3 query.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $query = $this->askForQuery($input, $output);
        $attendanceMode = $this->askAttendanceMode($input, $output);

        $locationId = new LocationId(Uuid::NIL);
        if (!$attendanceMode->sameAs(AttendanceMode::online())) {
            $locationId = $this->askForPhysicalLocation($input, $output);
        }

        $count = $this->searchResultsGenerator->count($query);

        if ($count <= 0) {
            $output->writeln('Query found 0 events to update');
            return 0;
        }

        if (!$this->askConfirmation($input, $output, $count)) {
            return 0;
        }

        $exceptions = [];
        $events = $this->searchResultsGenerator->search($query);
        $progressBar = new ProgressBar($output, $count);

        foreach ($events as $id => $event) {
            try {
                $this->commandBus->dispatch(new UpdateAttendanceMode($id, $attendanceMode));

                $this->commandBus->dispatch(new UpdateLocation($id, $locationId));
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

    private function askForQuery(InputInterface $input, OutputInterface $output): string
    {
        $question = new Question('Provide SAPI 3 query for events to update' . \PHP_EOL);

        return $this->getHelper('question')->ask($input, $output, $question);
    }

    private function askForPhysicalLocation(InputInterface $input, OutputInterface $output): LocationId
    {
        $question = new Question('Provide UUID of the physical location the mixed/offline event will take place' . \PHP_EOL);
        $question->setValidator(static function (string $location) {
            if ($location === Uuid::NIL) {
                throw new RuntimeException('Mixed and offline events require a physical location and not a nil location');
            }
            return $location;
        });

        return new LocationId($this->getHelper('question')->ask($input, $output, $question));
    }

    private function askAttendanceMode(InputInterface $input, OutputInterface $output): AttendanceMode
    {
        $question =  new ChoiceQuestion(
            'What should be the new attendance mode?',
            [
                AttendanceMode::offline()->toString(),
                AttendanceMode::online()->toString(),
                AttendanceMode::mixed()->toString(),
            ]
        );

        $question->setErrorMessage('Invalid attendance mode: %s');

        return new AttendanceMode($this->getHelper('question')->ask($input, $output, $question));
    }

    private function askConfirmation(InputInterface $input, OutputInterface $output, int $count): bool
    {
        $question = new ConfirmationQuestion(
            'This action will update the attendance mode of ' . $count . ' events, continue? [y/N]',
            false
        );

        return $this->getHelper('question')->ask($input, $output, $question);
    }
}
