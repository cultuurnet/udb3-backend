<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\UDB2\EventCdbXmlServiceInterface;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EventCdbXmlCommand extends Command
{
    public function configure()
    {
        $this
            ->setName('event:cdbxml')
            ->setDescription('Fetch the cdbxml of an event.')
            ->addArgument(
                'cdbid',
                InputArgument::REQUIRED
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EventCdbXmlServiceInterface $cdbXmlFetcher */
        $cdbXmlFetcher = $this->getSilexApplication()['udb2_event_cdbxml'];

        $xml = $cdbXmlFetcher->getCdbXmlOfEvent($input->getArgument('cdbid'));

        $output->writeln($xml);
    }
}
