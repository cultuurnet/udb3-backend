<?php

namespace CultuurNet\UDB3\Silex\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SearchCacheClearCommand extends AbstractSearchCacheCommand
{
    protected function configure()
    {
        $this
            ->setName('search:clear')
            ->setDescription(
                'Ensures the search cache is cleared.'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cacheManager = $this->getCacheManager();
        $cacheManager->clearCache();
        $output->writeln('Search cache cleared.');
    }
}
