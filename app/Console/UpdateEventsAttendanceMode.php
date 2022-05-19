<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateAttendanceMode;
use CultuurNet\UDB3\Model\ValueObject\Online\AttendanceMode;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use Exception;
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
            ['created' => 'asc'],
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

        $count = $this->searchResultsGenerator->count($query);

        if ($count <= 0) {
            $output->writeln('Query found 0 events to update');
            return 0;
        }

        $confirmation = $this->getHelper('question')
            ->ask(
                $input,
                $output,
                new ConfirmationQuestion(
                    'This action will update the attendance mode of ' . $count . ' events, continue? [y/N]',
                    false
                )
            );

        if (!$confirmation) {
            return 0;
        }

        $exceptions = [];
        $events = $this->searchResultsGenerator->search($query);
        $progressBar = new ProgressBar($output, $count);

        foreach ($events as $id => $event) {
            try {
                $this->commandBus->dispatch(new UpdateAttendanceMode($id, $attendanceMode));
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

    private function askForQuery($input, $output): string
    {
        $question = new Question('Provide SAPI 3 query for events to update' . \PHP_EOL);

        return $this->getHelper('question')->ask($input, $output, $question);
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
}
