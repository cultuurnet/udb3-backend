<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBus;
use Broadway\Serializer\Serializer;
use Broadway\Serializer\SimpleInterfaceSerializer;
use CultuurNet\UDB3\Broadway\Domain\DomainMessageIsReplayed;
use CultuurNet\UDB3\Event\Events\EventProjectedToJSONLD;
use CultuurNet\UDB3\EventBus\Middleware\InterceptingMiddleware;
use CultuurNet\UDB3\EventBus\Middleware\ReplayFlaggingMiddleware;
use CultuurNet\UDB3\EventSourcing\DBAL\EventStream;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Organizer\OrganizerProjectedToJSONLD;
use CultuurNet\UDB3\Place\Events\PlaceProjectedToJSONLD;
use CultuurNet\UDB3\AggregateType;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class ReplayCommand extends AbstractCommand
{
    private const TABLES_TO_PURGE = [
        'event_permission_readmodel' => 'event_id',
        'event_relations' => 'event',
        'offer_metadata' => 'id',
        'organizer_permission_readmodel' => 'organizer_id',
        'place_permission_readmodel'=> 'place_id',
        'place_relations' => 'place',
    ];

    public const OPTION_START_ID = 'start-id';
    public const OPTION_DELAY = 'delay';
    public const OPTION_CDBID = 'cdbid';
    public const OPTION_NO_AMQP_MESSAGES_AFTER_REPLAY = 'no-amqp-messages-after-replay';
    public const OPTION_FORCED = 'force';

    private Connection $connection;

    private Serializer $payloadSerializer;

    private EventBus $eventBus;

    /**
     * Note that we pass $config by reference here.
     * We need this because the replay command overrides configuration properties for active subscribers.
     */
    public function __construct(
        CommandBus $commandBus,
        Connection $connection,
        Serializer $payloadSerializer,
        EventBus $eventBus
    ) {
        parent::__construct($commandBus);
        $this->connection = $connection;
        $this->payloadSerializer = $payloadSerializer;
        $this->eventBus = $eventBus;
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
                'Aggregate type to replay events from. One of: ' . $aggregateTypeEnumeration . '.'
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
                self::OPTION_NO_AMQP_MESSAGES_AFTER_REPLAY,
                null,
                InputOption::VALUE_NONE,
                'Disables the publication of EventProjectedToJSONLD, PlaceProjectedToJSONLD and OrganizerProjectedToJSONLD messages to the AMQP exchange that normally happens at the end of the replay.'
            )
            ->addOption(
                self::OPTION_FORCED,
                null,
                InputOption::VALUE_NONE,
                'Do not ask for confirmation, immediately start the replay.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->askConfirmation($input, $output)) {
            return 0;
        }

        $delay = (int) $input->getOption(self::OPTION_DELAY);

        $aggregateType = $this->getAggregateType($input);

        $startId = $input->getOption(self::OPTION_START_ID);
        if ($startId !== null) {
            $startId = (int) $startId;
        }

        $cdbids = $input->getOption(self::OPTION_CDBID);

        if ($cdbids !== null) {
            $cdbids = array_map(
                fn (string $cdbid) => new UUID($cdbid),
                $cdbids
            );
        }

        // since we cannot catch errors when multiple cdbids are giving
        // and this Command is mostly run via Jenkins with exactly 1 cdbid
        // we will only fix this for the first cdbid
        if ($cdbids !== null && sizeof($cdbids) === 1) {
            $this->purgeReadmodels($cdbids[0]);
        }

        $stream = $this->getEventStream(
            $startId,
            $aggregateType,
            array_map(
                fn (UUID $cdbid) => $cdbid->toString(),
                $cdbids
            )
        );

        ReplayFlaggingMiddleware::startReplayMode();
        InterceptingMiddleware::startIntercepting(
            fn (DomainMessage $message) => $message->getPayload() instanceof EventProjectedToJSONLD ||
                $message->getPayload() instanceof PlaceProjectedToJSONLD ||
                $message->getPayload() instanceof OrganizerProjectedToJSONLD
        );

        foreach ($stream() as $eventStream) {
            if ($delay > 0) {
                // Delay has to be multiplied by the number of messages in this
                // particular chunk because in theory we handle more than 1
                // message per time. In reality the stream contains 1 message.
                // Multiply by 1000 to convert to microseconds.
                usleep($delay * $eventStream->getIterator()->count() * 1000);
            }

            $this->logStream($eventStream, $output, $stream, 'before_publish');
            $this->eventBus->publish($eventStream);
            $this->logStream($eventStream, $output, $stream, 'after_publish');
        }

        ReplayFlaggingMiddleware::stopReplayMode();
        InterceptingMiddleware::stopIntercepting();

        if ((bool) $input->getOption(self::OPTION_NO_AMQP_MESSAGES_AFTER_REPLAY) !== true) {
            // Remove replay flag from intercepted ProjectedToJSONLD domain messages before publishing to the event bus,
            // so the AMQPPublisher will actually get to process them.
            $intercepted = InterceptingMiddleware::getInterceptedMessagesWithUniquePayload();
            $interceptedAsArray = $intercepted->getIterator()->getArrayCopy();
            $interceptedCount = count($interceptedAsArray);
            $intercepted = new DomainEventStream(
                array_map(
                    function (DomainMessage $domainMessage): DomainMessage {
                        return $domainMessage->andMetadata(
                            new Metadata([DomainMessageIsReplayed::METADATA_REPLAY_KEY => false])
                        );
                    },
                    $interceptedAsArray
                )
            );

            $output->writeln('Publishing ' . $interceptedCount . ' ProjectedToJSONLD message(s) to the internal event bus (and AMQP)...');
            $this->eventBus->publish($intercepted);
            $output->writeln($interceptedCount . ' ProjectedToJSONLD message(s) published!');
            $output->writeln('Note: Extra ProjectedToJSONLD messages for related events/places are published indirectly.');
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

    private function purgeReadmodels(UUID $cdbid): void
    {
        foreach (self::TABLES_TO_PURGE as $tableName => $columnName) {
            $this->connection->delete(
                $tableName,
                [$columnName => $cdbid->toString()]
            );
        }
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

    private function askConfirmation(InputInterface $input, OutputInterface $output): bool
    {
        if ($input->getOption(self::OPTION_FORCED) === true) {
            return true;
        }

        $aggregateType = $this->getAggregateType($input);
        $startId = $input->getOption(self::OPTION_START_ID);
        $cdbids = $input->getOption(self::OPTION_CDBID);

        $message = 'Are you sure you want to replay all events? [y/N] ';

        if ($aggregateType || $startId || $cdbids) {
            $options = [];
            if ($aggregateType) {
                $options[] = 'aggregate type: ' . $aggregateType->toString();
            }

            if ($startId) {
                $options[] = 'start id: ' . $startId;
            }

            if ($cdbids) {
                $options[] = 'given cdbids';
            }

            $message = 'Are you sure you want to replay events ( ' . implode(', ', $options) . ' )? [y/N]';
        }

        return $this
            ->getHelper('question')
            ->ask(
                $input,
                $output,
                new ConfirmationQuestion($message, false)
            );
    }
}
