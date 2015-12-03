<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex\Console;

use Knp\Command\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;

class AMQPListenCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('amqp-listen')
            ->setDescription('Listens for incoming messages from a message broker with AMQP')
            ->addOption(
                'delay',
                null,
                InputOption::VALUE_REQUIRED,
                'Seconds to delay the handling of each incoming message'
            );
    }

    private function handleSignal(OutputInterface $output, $signal)
    {
        $output->writeln('Signal received, halting.');
        exit;
    }

    private function registerSignalHandlers(OutputInterface $output)
    {
        $handler = function ($signal) use ($output) {
            $this->handleSignal($output, $signal);
        };

        foreach ([SIGINT, SIGTERM, SIGQUIT] as $signal) {
            pcntl_signal($signal, $handler);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->registerSignalHandlers($output);

        $delay = $input->getOption('delay');
        if (null !== $delay) {
            $app = $this->getSilexApplication();
            $app['amqp-execution-delay'] = $input->getOption('delay');
        }

        $output->writeln('Connecting...');
        $connection = $this->getAMQPConnection();
        $output->writeln('Connected. Listening for incoming messages...');

        $channel = $connection->channel(1);
        while (count($channel->callbacks) > 0) {
            pcntl_signal_dispatch();
            $channel->wait();
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

