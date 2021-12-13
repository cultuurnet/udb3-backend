<?php

declare(strict_types=1);

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

class ReplayCommand extends AbstractCommand
{
    public const OPTION_DISABLE_PUBLISHING = 'disable-publishing';
    public const OPTION_DISABLE_RELATED_OFFER_SUBSCRIBERS = 'disable-related-offer-subscribers';
    public const OPTION_START_ID = 'start-id';
    public const OPTION_DELAY = 'delay';
    public const OPTION_CDBID = 'cdbid';

    private Connection $connection;

    private Serializer $payloadSerializer;

    private EventBus $eventBus;

    private ConfigWriter $configWriter;

    /**
     * Note that we pass $config by reference here.
     * We need this because the replay command overrides configuration properties for active subscribers.
     */
    public function __construct(
        CommandBus $commandBus,
        Connection $connection,
        Serializer $payloadSerializer,
        EventBus $eventBus,
        ConfigWriter $configWriter
    ) {
        parent::__construct($commandBus);
        $this->connection = $connection;
        $this->payloadSerializer = $payloadSerializer;
        $this->eventBus = $eventBus;
        $this->configWriter = $configWriter;
    }

    protected function configure(): void
    {
        $aggregateTypeEnumeration = implode(
            ', ',
            AggregateType::getAllowedValues()
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
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $delay = (int) $input->getOption(self::OPTION_DELAY);

        $subscribers = $input->getOption('subscriber');
        if (!empty($subscribers)) {
            $this->setSubscribers($subscribers, $output);
        }

        $aggregateType = $this->getAggregateType($input);

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

    private function logStream(
        DomainEventStream $eventStream,
        OutputInterface $output,
        EventStream $stream,
        string $marker
    ): void {
        /** @var DomainMessage $message */
        foreach ($eventStream->getIterator() as $message) {
            $this->logMessage($output, $stream, $message, $marker);
        }
    }

    private function logMessage(
        OutputInterface $output,
        EventStream $stream,
        DomainMessage $message,
        string $marker
    ): void {
        $output->writeln(
            $stream->getLastProcessedId() . '. ' .
            $message->getRecordedOn()->toString() . ' ' .
            $message->getType() .
            ' (' . $message->getId() . ') ' . $marker
        );
    }

    private function setSubscribers(array $subscribers, OutputInterface $output): void
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

    private function disableRelatedOfferSubscribers(): void
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
     * @param string[] $cdbids
     */
    private function getEventStream(
        ?int $startId = null,
        AggregateType $aggregateType = null,
        array $cdbids = null
    ): EventStream {
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
            $eventStream = $eventStream->withAggregateType($aggregateType->toString());
        }

        if ($cdbids) {
            $eventStream = $eventStream->withCdbids($cdbids);
        }

        return $eventStream;
    }

    private function getAggregateType(InputInterface $input): ?AggregateType
    {
        $aggregateTypeInput = $input->getArgument('aggregate');

        $aggregateType = null;

        if (!empty($aggregateTypeInput)) {
            $aggregateType = new AggregateType($aggregateTypeInput);
        }

        return $aggregateType;
    }

    private function isPublishDisabled(InputInterface $input): bool
    {
        return $input->getOption(self::OPTION_DISABLE_PUBLISHING);
    }
}
