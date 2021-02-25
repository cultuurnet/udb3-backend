<?php

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBus;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventBus;
use Broadway\Serializer\Serializer;
use Broadway\Serializer\SimpleInterfaceSerializer;
use CultuurNet\UDB3\Broadway\EventHandling\ReplayModeEventBusInterface;
use CultuurNet\UDB3\EventSourcing\DBAL\EventStream;
use CultuurNet\UDB3\Silex\AggregateType;
use CultuurNet\UDB3\Silex\ConfigWriter;
use Doctrine\DBAL\Connection;
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
    public const OPTION_DISABLE_PUBLISHING = 'disable-publishing';
    public const OPTION_DISABLE_RELATED_OFFER_SUBSCRIBERS = 'disable-related-offer-subscribers';
    public const OPTION_START_ID = 'start-id';
    public const OPTION_DELAY = 'delay';
    public const OPTION_CDBID = 'cdbid';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Serializer
     */
    private $payloadSerializer;

    /**
     * @var EventBus
     */
    private $eventBus;

    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * Note that we pass $config by reference here. We need this because the replay command overrides configuration properties for active subscribers.
     */
    public function __construct(CommandBus $commandBus, Connection $connection, Serializer $payloadSerializer, EventBus $eventBus, ConfigWriter $configWriter)
    {
        parent::__construct($commandBus);
        $this->connection = $connection;
        $this->payloadSerializer = $payloadSerializer;
        $this->eventBus = $eventBus;
        $this->configWriter = $configWriter;
    }


    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $aggregateTypeEnumeration = implode(
            ', ',
            AggregateType::getConstants()
        );

        $this
            ->setName('replay')
            ->setDescription('Replay the event stream to the event bus with only read models attached.')
            ->addArgument(
                'aggregate',
                InputArgument::OPTIONAL,
                'Aggregate type to replay events from. One of: ' . $aggregateTypeEnumeration . '.',
                null
            )
            ->addOption(
                'subscriber',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
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
            )
            ->addOption(
                self::OPTION_DISABLE_RELATED_OFFER_SUBSCRIBERS,
                null,
                InputOption::VALUE_NONE,
                'Disables the event bus subscribers that react on relations between organizers, places and events'
            );
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $delay = (int) $input->getOption(self::OPTION_DELAY);

        $subscribers = $input->getOption('subscriber');
        if (!empty($subscribers)) {
            $this->setSubscribers($subscribers, $output);
        }

        $aggregateType = $this->getAggregateType($input, $output);

        $disableRelatedOfferSubscribers = $input->getOption(self::OPTION_DISABLE_RELATED_OFFER_SUBSCRIBERS);
        if ($disableRelatedOfferSubscribers) {
            $this->disableRelatedOfferSubscribers();
        }

        $startId = $input->getOption(self::OPTION_START_ID);
        $cdbids = $input->getOption(self::OPTION_CDBID);

        $stream = $this->getEventStream($startId, $aggregateType, $cdbids);

        if ($this->eventBus instanceof ReplayModeEventBusInterface) {
            $this->eventBus->startReplayMode();
        } else {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                'Warning! The current event bus does not flag replay messages. '
                . 'This might trigger unintended changes. Continue anyway? [y/N] ',
                false
            );

            if (!$helper->ask($input, $output, $question)) {
                return 0;
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
                $this->eventBus->publish($eventStream);
            }

            $this->logStream($eventStream, $output, $stream, 'after_publish');
        }

        if ($this->eventBus instanceof ReplayModeEventBusInterface) {
            $this->eventBus->stopReplayMode();
        }

        return 0;
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

    private function setSubscribers(array $subscribers, OutputInterface $output)
    {
        $subscribersString = implode(', ', $subscribers);
        $msg = 'Registering the following subscribers with the event bus: %s';
        $output->writeln(sprintf($msg, $subscribersString));

        $this->configWriter->merge(
            [
                'event_bus' => [
                    'subscribers' => $subscribers,
                ],
            ]
        );
    }

    private function disableRelatedOfferSubscribers()
    {
        $this->configWriter->merge(
            [
                'event_bus' => [
                    'disable_related_offer_subscribers' => true,
                ],
            ]
        );
    }

    /**
     * @param int $startId
     * @param AggregateType $aggregateType
     * @param string[] $cdbids
     * @return EventStream
     */
    private function getEventStream(
        $startId = null,
        AggregateType $aggregateType = null,
        $cdbids = null
    ) {
        $startId = $startId !== null ? (int) $startId : 0;

        $eventStream = new EventStream(
            $this->connection,
            $this->payloadSerializer,
            new SimpleInterfaceSerializer(),
            'event_store'
        );

        if ($startId > 0) {
            $eventStream = $eventStream->withStartId($startId);
        }

        if ($aggregateType) {
            $eventStream = $eventStream->withAggregateType($aggregateType);
        }

        if ($cdbids) {
            $eventStream = $eventStream->withCdbids($cdbids);
        }

        return $eventStream;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return AggregateType|null
     */
    private function getAggregateType(
        InputInterface $input,
        OutputInterface $output
    ) {
        $aggregateTypeInput = $input->getArgument('aggregate');

        $aggregateType = null;

        if (!empty($aggregateTypeInput)) {
            $aggregateType = AggregateType::get($aggregateTypeInput);
        }

        return $aggregateType;
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
