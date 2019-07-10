<?php

namespace CultuurNet\UDB3\Silex\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConcludeByCdbidCommand extends AbstractConcludeCommand
{
    public function configure()
    {
        $this
            ->setName('event:concludeByCdbid')
            ->setDescription('Concludes an event based on the provided cdbid.')
            ->addArgument(
                'cdbid',
                InputArgument::REQUIRED,
                'The cdbid to conclude.'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cdbid = $input->getArgument('cdbid');

        $this->dispatchConclude($cdbid);
    }
}
