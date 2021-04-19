<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\Storage\PurgeServiceInterface;
use CultuurNet\UDB3\Storage\PurgeServiceManager;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PurgeModelCommand
 *
 * @package CultuurNet\UDB3\Silex\Console
 */
class PurgeModelCommand extends Command
{
    /**
     * @var PurgeServiceManager
     */
    private $purgeServiceManager;

    public function __construct(PurgeServiceManager $purgeServiceManager)
    {
        parent::__construct();
        $this->purgeServiceManager = $purgeServiceManager;
    }


    protected function configure()
    {
        $this
            ->setName('purge')
            ->setDescription('Purge all read models');
    }

    /**
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $purgeServices = $this->getPurgeServices();
        $this->purge($purgeServices);

        return 0;
    }

    /**
     * @return PurgeServiceInterface[]
     */
    private function getPurgeServices(): array
    {
        return $this->purgeServiceManager->getReadModelPurgeServices();
    }

    /**
     * @param PurgeServiceInterface[] $purgeServices
     */
    private function purge(array $purgeServices)
    {
        foreach ($purgeServices as $purgeService) {
            $purgeService->purgeAll();
        }
    }
}
