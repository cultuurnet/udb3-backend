<?php

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\Silex\PurgeServiceProvider;
use CultuurNet\UDB3\Storage\PurgeServiceInterface;
use CultuurNet\UDB3\Storage\PurgeServiceManager;
use Knp\Command\Command;
use Silex\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PurgeModelCommand
 * @package CultuurNet\UDB3\Silex\Console
 */
class PurgeModelCommand extends Command
{
    const MODEL_ARGUMENT = 'model';

    const WRITE_MODEL = 'mysql-write';
    const READ_MODEL = 'mysql-read';

    protected function configure()
    {
        $this
            ->setName('purge')
            ->setDescription('Purge the specified model')
            ->addArgument(
                self::MODEL_ARGUMENT,
                InputArgument::REQUIRED,
                'Which model to purge: mysql-write, mysql-read'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $model = $input->getArgument(self::MODEL_ARGUMENT);

        if ($this->isModelValid($model)) {
            $purgeServices = $this->getPurgeServices($model);
            $this->purge($purgeServices);
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

        $purgeServiceManager = $this->getPurgeServiceManager();

        if (self::READ_MODEL === $model) {
            $purgeServices = $purgeServiceManager->getReadModelPurgeServices();
        } else  if (self::WRITE_MODEL === $model) {
            $purgeServices = $purgeServiceManager->getWriteModelPurgeServices();
        }

        return $purgeServices;
    }

    /**
     * @return PurgeServiceManager
     */
    private function getPurgeServiceManager()
    {
        $application = $this->getSilexApplication();

        return $application[PurgeServiceProvider::PURGE_SERVICE_MANAGER];
    }

    /**
     * @param PurgeServiceInterface[] $purgeServices
     */
    private function purge($purgeServices)
    {
        foreach($purgeServices as $purgeService) {
            $purgeService->purgeAll();
        }
    }

    /**
     * @param string $model
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
