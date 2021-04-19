<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\Storage\PurgeServiceManager;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        $this->setName('purge')->setDescription('Purge all read models');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->purgeServiceManager->getReadModelPurgeServices() as $purgeService) {
            $purgeService->purgeAll();
        }

        return 0;
    }
}
