<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\Silex\DatabaseSchemaInstaller;
use CultuurNet\UDB3\Silex\DatabaseSchemaInstallerInterface;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('install')
            ->setDescription('Install the silex application (db schema insertion, etc.)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getDatabaseSchemaInstaller()->installSchema();

        $output->writeln('Database schema installed.');
    }

    /**
     * @return DatabaseSchemaInstallerInterface
     */
    protected function getDatabaseSchemaInstaller()
    {
        $app = $this->getSilexApplication();
        return $app['database.installer'];
    }

}
