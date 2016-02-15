<?php

namespace CultuurNet\UDB3\Silex\Console;

use Knp\Command\Command;
use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use CultuurNet\UDB3\Silex\PurgeServiceProvider;
use CultuurNet\UDB3\Storage\PurgeServiceInterface;
use CultuurNet\UDB3\Storage\PurgeServiceManager;

/**
 * Class PurgeModelCommand
 * @package CultuurNet\UDB3\Silex\Console
 */
class PurgeModelCommand extends Command
{
    const MODEL_OPTION = 'model';

    const WRITE_MODEL = 1;
    const READ_MODEL = 2;
    const REDIS_READ_MODEL = 3;

    protected function configure()
    {
        $this
            ->setName('purge')
            ->setDescription('Purge the specified model')
            ->addOption(
                self::MODEL_OPTION,
                null,
                InputOption::VALUE_REQUIRED,
                'Which model to purge (1 = MySQL Write, 2 = MySQL Read, 3 = Redis Read)'
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
            $purgeServices = $this->getPurgeServices($model);
            $this->purgeServices($purgeServices);
        } else {
            $output->writeln('Model option is not valid!');
        }

        return 0;
    }

    /**
     * @param $model
     * @return PurgeServiceInterface[]
     */
    private function getPurgeServices($model)
    {
        $purgeServices = array();

        $application = $this->getSilexApplication();
        /**
         * @var PurgeServiceManager $purgeServiceManager
         */
        $purgeServiceManager = $application[PurgeServiceProvider::PURGE_SERVICE_MANAGER];

        if (self::READ_MODEL === $model) {
            $purgeServices = $purgeServiceManager->getReadModelPurgeServices();
        } else  if (self::WRITE_MODEL === $model) {
            $purgeServices = $purgeServiceManager->getWriteModelPurgeServices();
        }

        return $purgeServices;
    }

    /**
     * @param PurgeServiceInterface[] $purgeServices
     */
    private function purgeServices($purgeServices)
    {
        foreach($purgeServices as $purgeService) {
            $purgeService->purgeAll();
        }
    }

    /**
     * @param int $model
     * @return bool
     */
    private function isModelValid($model)
    {
        return (
            self::READ_MODEL === $model ||
            self::WRITE_MODEL === $model
        );
    }
}