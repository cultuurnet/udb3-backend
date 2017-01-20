<?php

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBusInterface;
use Carbon\Carbon;
use CultuurNet\UDB3\Event\Commands\Conclude;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Silex\Impersonator;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConcludeCommand extends Command
{
    const TIMEZONE = 'Europe/Brussels';

    public function configure()
    {
        $this
            ->setName('event:conclude')
            ->setDescription('Conclude events of which the end date falls in a specific date range.')
            ->addArgument(
                'lower-boundary',
                InputArgument::OPTIONAL,
                'The lower boundary of the end date range',
                Carbon::yesterday(self::TIMEZONE)->toDateTimeString()
            )
            ->addArgument(
                'upper-boundary',
                InputArgument::OPTIONAL,
                'The upper boundary of the end date range',
                Carbon::yesterday(self::TIMEZONE)->setTime(23, 59, 59)->toDateTimeString()
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lowerBoundaryInput = $input->getArgument('lower-boundary');

        $lowerDateBoundary = null;
        if ($lowerBoundaryInput !== '*') {
            $lowerDateBoundary = new Carbon(
                $lowerBoundaryInput,
                self::TIMEZONE
            );
        }

        $upperDateBoundary = new Carbon(
            $input->getArgument('upper-boundary'),
            self::TIMEZONE
        );

        if ($lowerDateBoundary && $lowerDateBoundary->greaterThanOrEqualTo($upperDateBoundary)) {
            throw new \InvalidArgumentException(
                'lower-boundary needs to be before upper-boundary'
            );
        }

        $latestAllowedUpperBoundary = Carbon::yesterday(self::TIMEZONE)->setTime(23, 59, 59);
        if ($upperDateBoundary->greaterThan($latestAllowedUpperBoundary)) {
            throw new \InvalidArgumentException(
                'your upper boundary is too high, latest allowed upper boundary is ' . $latestAllowedUpperBoundary->toDateTimeString()
            );
        }

        $finder = $this->getFinder();

        $sapiDateRange = $this->buildDateRangeString($lowerDateBoundary, $upperDateBoundary);

        $query = "availableto:{$sapiDateRange}";
        $output->writeln('Executing search query: ' . $query);

        $results = $finder->search($query);

        $commandBus = $this->getCommandBus();

        foreach ($results as $result) {
            print_r($result);

            $commandBus->dispatch(
                new Conclude($input->getArgument('cdbid'))
            );
        }
    }

    private function buildDateRangeString(Carbon $lowerDateBoundary = null, Carbon $upperDateBoundary = null)
    {
        $format = 'Y-m-d\TH:i:s\Z';

        $from = $lowerDateBoundary ? $lowerDateBoundary->tz('UTC')->format($format) : '*';
        $to = $upperDateBoundary->tz('UTC')->format($format);

        return "[{$from} TO {$to}]";
    }

    /**
     * @return ResultsGenerator
     */
    private function getFinder()
    {
        $app = $this->getSilexApplication();

        $finder = new ResultsGenerator($app['search_service']);

        return $finder;
    }

    /**
     * @return CommandBusInterface
     */
    private function getCommandBus()
    {
        $app = $this->getSilexApplication();

        /** @var Impersonator $impersonator */
        $impersonator = $app['impersonator'];

        // Before initializing the command bus, impersonate the system user.
        $impersonator->impersonate($app['udb3_system_user_metadata']);

        $commandBus = $app['event_command_bus'];

        return $commandBus;
    }
}
