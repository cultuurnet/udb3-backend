<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use Closure;
use CultuurNet\UDB3\Broadway\AMQP\ConsumerInterface;
use Knp\Command\Command;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsumeCommand extends Command
{
    private string $consumerName;
    private ContainerInterface $container;
    private ?Closure $heartBeat;

    public function __construct(string $name, string $consumerName, ContainerInterface $container, Closure $heartBeat)
    {
        parent::__construct($name);

        $this->consumerName = $consumerName;
        $this->container = $container;
        $this->heartBeat = $heartBeat;
    }

    private function handleSignal(OutputInterface $output, $signal): void
    {
        $output->writeln('Signal received, halting.');
        exit;
    }

    private function registerSignalHandlers(OutputInterface $output): void
    {
        $handler = function ($signal) use ($output) {
            $this->handleSignal($output, $signal);
        };

        foreach ([SIGINT, SIGTERM, SIGQUIT] as $signal) {
            pcntl_signal($signal, $handler);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->registerSignalHandlers($output);

        $output->writeln('Connecting...');
        $channel = $this->getChannel();
        $output->writeln('Connected. Listening for incoming messages...');

        while (count($channel->callbacks) > 0) {
            if ($this->heartBeat) {
                call_user_func($this->heartBeat);
            }

            pcntl_signal_dispatch();

            try {
                $channel->wait();
            } catch (AMQPTimeoutException $e) {
                // Ignore this one.
            }
        }

        return 0;
    }

    private function getChannel(): AMQPChannel
    {
        /** @var ConsumerInterface $consumer */
        $consumer = $this->container->get($this->consumerName);
        $channel = $consumer->getChannel();

        if (!$channel instanceof AMQPChannel) {
            throw new RuntimeException(
                'The consumer channel is not of the expected type AMQPChannel'
            );
        }

        return $channel;
    }
}
