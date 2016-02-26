<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventBusInterface;
use Broadway\Serializer\SimpleInterfaceSerializer;
use CultuurNet\UDB3\EventSourcing\DBAL\EventStream;
use Knp\Command\Command;
use Silex\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ReplayCommand
 * @package CultuurNet\UDB3\Silex\Console
 */
class ReplayCommand extends Command
{
    const DISABLE_OPTION_PUBLISHING = 'disable-publishing';
    const DISABLE_OPTION_LOGGING = 'disable-logging';

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('replay')
            ->setDescription('Replay the event stream to the event bus with only read models attached.')
            ->addArgument(
                'store',
                InputArgument::OPTIONAL,
                'Event store to replay events from',
                'events'
            )
            ->addOption(
                'cache',
                null,
                InputOption::VALUE_REQUIRED,
                'Alternative cache factory method to use, specify the service suffix, for example "redis"'
            )
            ->addOption(
                'subscriber',
                null,
                InputOption::VALUE_IS_ARRAY|InputOption::VALUE_OPTIONAL,
                'Subscribers to register with the event bus. If not specified, all subscribers will be registered.'
            )
            ->addOption(
                self::DISABLE_OPTION_PUBLISHING,
                null,
                InputOption::VALUE_NONE,
                'It is possible to disable publishing'
            )
            ->addOption(
                self::DISABLE_OPTION_LOGGING,
                null,
                InputOption::VALUE_NONE,
                'It is possible to disable logging'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cache = $input->getOption('cache');
        if ($cache) {
            $cacheServiceName = 'cache-' . $cache;
            /** @var Application $app */
            $app = $this->getSilexApplication();

            $app['cache'] = $app->share(
                function (Application $app) use ($cacheServiceName) {
                    return $app[$cacheServiceName];
                }
            );
        }

        $subscribers = $input->getOption('subscriber');
        if (!empty($subscribers)) {
            $output->writeln(
                'Registering the following subscribers with the event bus: ' . implode(', ', $subscribers)
            );
            $this->setSubscribers($subscribers);
        }

        $store = $this->getStore($input, $output);

        $stream = $this->getEventStream($store);

        $eventBus = $this->getEventBus();

        /** @var DomainEventStream $eventStream */
        foreach ($stream() as $eventStream) {
            /** @var DomainMessage $message */
            foreach ($eventStream->getIterator() as $message) {
                if (!$this->isLoggingDisabled($input)) {
                    $output->writeln(
                        $message->getRecordedOn()->toString() . ' ' .
                        $message->getType() .
                        ' (' . $message->getId() . ')'
                    );
                }
            }

            if (!$this->isPublishDisabled($input)) {
                $eventBus->publish($eventStream);
            }
        }
    }

    /**
     * @return EventBusInterface
     */
    private function getEventBus()
    {
        $app = $this->getSilexApplication();

        // @todo Limit the event bus to read projections.
        return $app['event_bus'];
    }

    /**
     * @param $subscribers
     */
    private function setSubscribers($subscribers) {
        $app = $this->getSilexApplication();

        $config = $app['config'];
        $config['event_bus']['subscribers'] = $subscribers;
        $app['config'] = $config;
    }

    /**
     * @param string $store
     * @return EventStream
     */
    private function getEventStream($store = 'events')
    {
        $app = $this->getSilexApplication();

        return new EventStream(
            $app['dbal_connection'],
            $app['eventstore_payload_serializer'],
            new SimpleInterfaceSerializer(),
            $store
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    private function getStore(InputInterface $input, OutputInterface $output)
    {
        $validStores = [
            'events',
            'places',
            'organizers',
            'variations'
        ];

        $store = $input->getArgument('store');

        if (!in_array($store, $validStores)) {
            throw new \RuntimeException(
                'Invalid store "' . $store . '"", use one of: ' .
                implode(', ', $validStores)
            );
        }

        return $store;
    }

    /**
     * @param InputInterface $input
     * @return bool
     */
    private function isPublishDisabled(InputInterface $input)
    {
        return $input->getOption(self::DISABLE_OPTION_PUBLISHING);
    }

    /**
     * @param InputInterface $input
     * @return bool
     */
    private function isLoggingDisabled(InputInterface $input)
    {
        return $input->getOption(self::DISABLE_OPTION_LOGGING);
    }
}
