<?php

namespace CultuurNet\UDB3\Silex\Console;

use Doctrine\DBAL\Connection;
use Knp\Command\Command;
use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PurgeModelCommand extends Command
{
    const MODEL_OPTION = 'model';

    const MYSQL_WRITE_MODEL = 1;
    const MYSQL_READ_MODEL = 2;
    const REDIS_READ_MODEL = 3;

    protected function configure()
    {
        $this
            ->setName('clean')
            ->setDescription('Clean the specified model')
            ->addOption(
                self::MODEL_OPTION,
                null,
                InputOption::VALUE_REQUIRED,
                'Which model to clean (1 = MySQL Write, 2 = MySQL Read, 3 = Redis Read)'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $model = intval($input->getOption(self::MODEL_OPTION));

        if ($this->isModelValid($model)) {
            $models = $this->getMySQLModels($model);
            $this->cleanModels($models);
        } else {
            $output->writeln('Model option is not valid!');
        }

        return 0;
    }

    /**
     * @param int $model
     * @return null|\string[]
     */
    private function getMySQLModels($model)
    {
        $models = null;

        if (self::MYSQL_WRITE_MODEL === $model) {
            $models = $this->getMySQLWriteModels();
        } else if (self::MYSQL_READ_MODEL === $model) {
            $models = $this->getMySQLReadModels();
        }

        return $models;
    }

    /**
     * @return string[]
     */
    private function getMySQLWriteModels()
    {
        return array(
            'events',
            'media_objects',
            'organizers',
            'places',
            'variations'
        );
    }

    /**
     * @return string[]
     */
    private function getMySQLReadModels()
    {
        return array(
            'event_permission_readmodel',
            'event_relations',
            'event_variation_search_index',
            'index_readmodel'
        );
    }

    /**
     * @param string[] $models
     */
    private function cleanModels($models)
    {
        $application = $this->getSilexApplication();
        /**
         * @var Connection $connection
         */
        $connection = $application['dbal_connection'];
        $queryBuilder = $connection->createQueryBuilder();

        foreach($models as $model) {
            $queryBuilder->delete($model);
            $queryBuilder->execute();
        }
    }

    /**
     * @param int $model
     * @return bool
     */
    private function isModelValid($model)
    {
        return (
            self::MYSQL_READ_MODEL === $model ||
            self::MYSQL_WRITE_MODEL === $model
        );
    }
}