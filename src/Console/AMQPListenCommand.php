<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex\Console;

use Knp\Command\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
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
        $output->writeln('Connecting...');
        $connection = $this->getAMQPConnection();

        $channel = $connection->channel();

        while (count($channel->callbacks) > 0) {
            $connection->wait();
        }
    }

    /**
     * @return AMQPStreamConnection
     */
    protected function getAMQPConnection()
    {
        $app = $this->getSilexApplication();

        return $app['amqp-connection'];
    }
}
