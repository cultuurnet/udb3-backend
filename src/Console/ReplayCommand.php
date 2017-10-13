<?php

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventBusInterface;
use Broadway\Serializer\SimpleInterfaceSerializer;
use CultuurNet\Broadway\EventHandling\ReplayModeEventBusInterface;
use CultuurNet\UDB3\EventSourcing\DBAL\EventStream;
use Silex\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Class ReplayCommand
 *
 * @package CultuurNet\UDB3\Silex\Console
 */
class ReplayCommand extends AbstractCommand
{
    const OPTION_DISABLE_PUBLISHING = 'disable-publishing';
    const OPTION_START_ID = 'start-id';
    const OPTION_DELAY = 'delay';
    const OPTION_CDBID = 'cdbid';

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
                self::OPTION_DISABLE_PUBLISHING,
                null,
                InputOption::VALUE_NONE,
                'Disable publishing to the event bus.'
            )
            ->addOption(
                self::OPTION_START_ID,
                null,
                InputOption::VALUE_REQUIRED,
                'The id of the row to start the replay from.'
            )
            ->addOption(
                self::OPTION_DELAY,
                null,
                InputOption::VALUE_REQUIRED,
                'Delay per message, in milliseconds.',
                0
            )
            ->addOption(
                self::OPTION_CDBID,
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'An array of cdbids of the aggregates to be replayed.'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $delay = (int) $input->getOption(self::OPTION_DELAY);

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

        $store = $this->getStore($input);

        $startId = (int) $input->getOption(self::OPTION_START_ID);
        $cdbids = $input->getOption(self::OPTION_CDBID);
        $stream = $this->getEventStream($store, $startId, $cdbids);

        $eventBus = $this->getEventBus();

        if ($eventBus instanceof ReplayModeEventBusInterface) {
            $eventBus->startReplayMode();
        } else {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                'Warning! The current event bus does not flag replay messages. '
                . 'This might trigger unintended changes. Continue anyway? [y/N] ',
                false
            );

            if (!$helper->ask($input, $output, $question)) {
                return;
            }
        }

        /** @var DomainEventStream $eventStream */
        foreach ($stream() as $eventStream) {
            if ($delay > 0) {
                // Delay has to be multiplied by the number of messages in this
                // particular chunk because in theory we handle more than 1
                // message per time. In reality the stream contains 1 message.
                // Multiply by 1000 to convert to microseconds.
                usleep($delay * $eventStream->getIterator()->count() * 1000);
            }

            $this->logStream($eventStream, $output, $stream, 'before_publish');

            if (!$this->isPublishDisabled($input)) {
                $eventBus->publish($eventStream);
            }

            $this->logStream($eventStream, $output, $stream, 'after_publish');
        }

        if ($eventBus instanceof ReplayModeEventBusInterface) {
            $eventBus->stopReplayMode();
        }
    }

    /**
     * @param DomainEventStream $eventStream
     * @param OutputInterface $output
     * @param EventStream $stream
     * @param string $marker
     */
    private function logStream(
        DomainEventStream $eventStream,
        OutputInterface $output,
        EventStream $stream,
        $marker
    ) {
        /** @var DomainMessage $message */
        foreach ($eventStream->getIterator() as $message) {
            $this->logMessage($output, $stream, $message, $marker);
        }
    }

    /**
     * @param OutputInterface $output
     * @param EventStream $stream
     * @param DomainMessage $message
     * @param string $marker
     */
    private function logMessage(
        OutputInterface $output,
        EventStream $stream,
        DomainMessage $message,
        $marker
    ) {
        $output->writeln(
            $stream->getLastProcessedId() . '. ' .
            $message->getRecordedOn()->toString() . ' ' .
            $message->getType() .
            ' (' . $message->getId() . ') ' . $marker
        );
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
    private function setSubscribers($subscribers)
    {
        $app = $this->getSilexApplication();

        $config = $app['config'];
        $config['event_bus']['subscribers'] = $subscribers;
        $app['config'] = $config;
    }

    /**
     * @param string $store
     * @param int|null $startId
     * @param string[] $cdbids
     * @return EventStream
     */
    private function getEventStream(
        $store = 'events',
        $startId = null,
        $cdbids = null
    ) {
        $app = $this->getSilexApplication();

        $eventStream = new EventStream(
            $app['dbal_connection'],
            $app['eventstore_payload_serializer'],
            new SimpleInterfaceSerializer(),
            $store
        );

        if ($startId) {
            $eventStream = $eventStream->withStartId($startId);
        }

        if ($cdbids) {
            $eventStream = $eventStream->withCdbids($cdbids);
        }

        // Older domain messages in the events, places, and organizers
        // stores are missing some metadata. Add it using the offer locator
        // class.
        if (in_array($store, ['events', 'places', 'organizers'])) {
            $offerLocator = $app[$store . '_locator_event_stream_decorator'];
            $eventStream = $eventStream->withDomainEventStreamDecorator($offerLocator);
        }

        return $eventStream;
    }

    /**
     * @param InputInterface  $input
     * @return mixed
     */
    private function getStore(InputInterface $input)
    {
        $validStores = [
            'events',
            'places',
            'organizers',
            'variations',
            'media_objects',
            'roles',
            'labels',
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
        return $input->getOption(self::OPTION_DISABLE_PUBLISHING);
    }
}
