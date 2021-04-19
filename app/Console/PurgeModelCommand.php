<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\Storage\PurgeServiceInterface;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PurgeModelCommand extends Command
{
    /**
     * @var PurgeServiceInterface[]
     */
    private $purgeServices;

    /**
     * @param PurgeServiceInterface[] $purgeServices
     */
    public function __construct(array $purgeServices)
    {
        parent::__construct();
        $this->purgeServices = $purgeServices;
    }

    protected function configure()
    {
        $this->setName('purge')->setDescription('Purge all read models');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->purgeServices as $purgeService) {
            $purgeService->purgeAll();
        }

        return 0;
    }
}
