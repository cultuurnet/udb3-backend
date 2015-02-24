<?php
/**
 * @file
 */

namespace CultuurNet\UDB3Silex\Console;

use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AMQPListenCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('amqp-listen')
            ->setDescription('Listens for incoming messages from a message broker with AMQP');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getAMQPConsumerManager()->listen();
    }

    protected function getAMQPConsumerManager()
    {
        $app = $this->getSilexApplication();

        return $app['amqp-consumer-manager'];
    }
}
