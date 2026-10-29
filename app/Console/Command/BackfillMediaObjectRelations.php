<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\Serializer\Serializer;
use Broadway\Serializer\SimpleInterfaceSerializer;
use CultuurNet\UDB3\EventSourcing\DBAL\EventStream;
use CultuurNet\UDB3\Media\ReadModel\Relations\MediaObjectRelationsProjector;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Fills media_object_relations from the image events in the event store. Only the media object relations projector
 * handles the events, so no other read model is touched. Adding a relation twice is ignored, so it is safe to run again.
 */
final class BackfillMediaObjectRelations extends Command
{
    private const OPTION_START_ID = 'start-id';

    private Connection $connection;
    private Serializer $payloadSerializer;
    private MediaObjectRelationsProjector $projector;

    public function __construct(
        Connection $connection,
        Serializer $payloadSerializer,
        MediaObjectRelationsProjector $projector
    ) {
        $this->connection = $connection;
        $this->payloadSerializer = $payloadSerializer;
        $this->projector = $projector;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('media-object:backfill-relations')
            ->setDescription('Fill media_object_relations from the image events in the event store.')
            ->addOption(
                self::OPTION_START_ID,
                null,
                InputOption::VALUE_REQUIRED,
                'The id of the event store row to start from.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $eventStream = (new EventStream(
            $this->connection,
            $this->payloadSerializer,
            new SimpleInterfaceSerializer(),
            'event_store'
        ))->withEventTypes(MediaObjectRelationsProjector::getHandledEventClasses());

        $startId = $input->getOption(self::OPTION_START_ID);
        if ($startId !== null) {
            $eventStream = $eventStream->withStartId((int) $startId);
        }

        $count = 0;
        foreach ($eventStream() as $domainEventStream) {
            foreach ($domainEventStream as $domainMessage) {
                $this->projector->handle($domainMessage);
                $count++;
            }

            if ($count % 1000 === 0) {
                $output->writeln($count . ' events handled, last id: ' . $eventStream->getLastProcessedId());
            }
        }

        $output->writeln($count . ' events handled, last id: ' . $eventStream->getLastProcessedId());

        return 0;
    }
}
