<?php

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use hanneskod\classtools\Iterator\ClassIterator;
use Knp\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class PermissionCommand extends Command
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('permission')
            ->setDescription('Get permission info on all authorizable commands');

        $this->output = new ConsoleOutput();
        $this->output->setFormatter(new OutputFormatter(true));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getAuthorizableCommands();
    }

    private function getAuthorizableCommands()
    {
        $finder = new Finder();
        $classes = new ClassIterator($finder->in('vendor/cultuurnet/udb3/src'));

        $classes->enableAutoloading();

        $authorizableClasses = $classes
            ->type(AuthorizableCommandInterface::class)
            ->where('isInstantiable');

        $table = new Table($this->output);
        $table->setHeaders(array('Command', 'Permission'));

        /** @var \ReflectionClass $class */
        foreach ($authorizableClasses as $class) {
            /** @var AuthorizableCommandInterface $authorizableClassInstance */
            $authorizableClassInstance = $class->newInstanceWithoutConstructor();

            $table->addRow(
                [
                    $class->getName(),
                    $authorizableClassInstance->getPermission(),
                ]
            );
        }

        $table->render();
    }
}
