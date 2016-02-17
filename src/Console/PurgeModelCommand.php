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
    const MODEL_OPTION = 'model';

    const WRITE_MODEL = 1;
    const READ_MODEL = 2;

    protected function configure()
    {
        $this
            ->setName('purge')
            ->setDescription('Purge the specified model')
            ->addArgument(
                self::MODEL_OPTION,
                InputArgument::REQUIRED,
                'Which model to purge (1 = MySQL Write, 2 = MySQL Read)'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $model = intval($input->getArgument(self::MODEL_OPTION));

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
