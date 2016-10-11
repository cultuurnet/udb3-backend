<?php

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Migrations\Version20161011000440;
use Elasticsearch\Client;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ElasticsearchCommand extends Command
{
    public function configure()
    {
        $this
            ->setName('elasticsearch:migrate')
            ->setDescription('Set up the latest elasticsearch indices.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Client $client */
        $client = $this->getSilexApplication()['elasticsearch_client'];

        $migration = new Version20161011000440($client);
        $migration->up();

        $output->writeln('Elasticsearch indices are up-to-date!');
    }
}
