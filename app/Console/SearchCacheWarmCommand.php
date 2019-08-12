<?php

namespace CultuurNet\UDB3\Silex\Console;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class SearchCacheWarmCommand extends AbstractSearchCacheCommand
{
    protected function configure()
    {
        $this
            ->setName('search:warmup')
            ->setDescription(
                'Ensures the search cache is warmed up after it was marked as outdated.'
            )
            ->addOption(
                'once',
                null,
                InputOption::VALUE_NONE,
                'If set, the command will exit after warmup and not warmup again when the cache is invalidated.'
            )
            ->addOption(
                'sleep',
                null,
                InputOption::VALUE_REQUIRED,
                'Amount of seconds to sleep between warmup checks. Defaults to 2.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->registerSignalHandlers($output);

        $repeat = !$input->getOption('once');

        $sleep = (int) $input->getOption('sleep');
        if (empty($sleep)) {
            $sleep = 2;
        }

        $verbose = (bool) $input->getOption('verbose');

        $cacheManager = $this->getCacheManager();

        if ($cacheManager instanceof LoggerAwareInterface && $verbose) {
            $cacheManager->setLogger(
                new ConsoleLogger(
                    $output,
                    [
                        LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL,
                        LogLevel::DEBUG => OutputInterface::VERBOSITY_NORMAL,
                    ]
                )
            );
        }

        do {
            $cacheManager->warmUpCacheIfNeeded();
            pcntl_signal_dispatch();

            if ($repeat) {
                if ($verbose) {
                    $output->writeln("Sleeping for {$sleep} seconds...");
                }

                sleep($sleep);
                pcntl_signal_dispatch();
            }
        } while ($repeat);
    }

    private function handleSignal(OutputInterface $output, $signal)
    {
        $output->writeln('Signal received, halting.');
        exit;
    }

    private function registerSignalHandlers(OutputInterface $output)
    {
        $handler = function ($signal) use ($output) {
            $this->handleSignal($output, $signal);
        };

        foreach ([SIGINT, SIGTERM, SIGQUIT] as $signal) {
            pcntl_signal($signal, $handler);
        }
    }
}
