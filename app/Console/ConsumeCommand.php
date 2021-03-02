<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\Broadway\AMQP\ConsumerInterface;
use Knp\Command\Command;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsumeCommand extends Command
{
    /**
     * @var string
     */
    private $consumerName;

    /**
     * @var string
     */
    private $heartBeatServiceName;

    /**
     * @param string $name
     * @param string $consumerName
     */
    public function __construct($name, $consumerName)
    {
        parent::__construct($name);

        $this->consumerName = $consumerName;
    }

    public function withHeartBeat($heartBeatServiceName)
    {
        $c = clone $this;
        $c->heartBeatServiceName = $heartBeatServiceName;
        return $c;
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

        $output->writeln('Connecting...');
        $channel = $this->getChannel();
        $output->writeln('Connected. Listening for incoming messages...');

        $heartBeat = $this->getHeartBeat();

        while (count($channel->callbacks) > 0) {
            if ($heartBeat) {
                $heartBeat($this->getSilexApplication());
            }

            pcntl_signal_dispatch();

            try {
                $channel->wait(null, true, 4);
            } catch (AMQPTimeoutException $e) {
                // Ignore this one.
            }
        }

        return 0;
    }

    /**
     * @return AMQPChannel
     */
    protected function getChannel()
    {
        $app = $this->getSilexApplication();

        /** @var ConsumerInterface $consumer */
        $consumer = $app[$this->consumerName];
        $channel = $consumer->getChannel();

        if (!$channel instanceof AMQPChannel) {
            throw new RuntimeException(
                'The consumer channel is not of the expected type AMQPChannel'
            );
        }

        return $channel;
    }

    /**
     * @return callable|null
     */
    protected function getHeartBeat()
    {
        $app = $this->getSilexApplication();

        $heartBeat = null;

        if ($this->heartBeatServiceName) {
            $heartBeat = $app[$this->heartBeatServiceName];

            if (!is_callable($heartBeat)) {
                throw new RuntimeException(
                    'The heartbeat service should be callable'
                );
            }
        }

        return $heartBeat;
    }
}
