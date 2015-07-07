<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\Search\CacheManager;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CacheWarmCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('search:warmup')
            ->setDescription(
                'Ensures the search cache is warmed up after it was marked as outdated.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->registerSignalHandlers($output);

        $cacheManager = $this->getCacheManager();

        while (true) {
            $cacheManager->warmupCacheIfNeeded();
            pcntl_signal_dispatch();
            sleep(1);
            pcntl_signal_dispatch();
            sleep(1);
            pcntl_signal_dispatch();
        }
    }

    /**
     * @return CacheManager
     */
    protected function getCacheManager()
    {
        $app = $this->getSilexApplication();
        return $app['search_cache_manager'];
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
