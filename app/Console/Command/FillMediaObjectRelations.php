<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Serializer\Serializer;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\Events\Image\ImagesImportedFromUDB2 as EventImagesImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\Image\ImagesUpdatedFromUDB2 as EventImagesUpdatedFromUDB2;
use CultuurNet\UDB3\Event\Events\ImageAdded as EventImageAdded;
use CultuurNet\UDB3\Event\Events\ImageRemoved as EventImageRemoved;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Media\ReadModel\Relations\MediaObjectRelationsProjector;
use CultuurNet\UDB3\Organizer\Events\ImageAdded as OrganizerImageAdded;
use CultuurNet\UDB3\Organizer\Events\ImageRemoved as OrganizerImageRemoved;
use CultuurNet\UDB3\Place\Events\Image\ImagesImportedFromUDB2 as PlaceImagesImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\Image\ImagesUpdatedFromUDB2 as PlaceImagesUpdatedFromUDB2;
use CultuurNet\UDB3\Place\Events\ImageAdded as PlaceImageAdded;
use CultuurNet\UDB3\Place\Events\ImageRemoved as PlaceImageRemoved;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Fills the media_object_relations table by passing all image related events from the event store, in order,
 * to the MediaObjectRelationsProjector. Running it more than once is safe.
 */
final class FillMediaObjectRelations extends Command
{
    private const MAX_RESULTS = 1000;

    private const EVENT_CLASSES = [
        EventImageAdded::class,
        EventImageRemoved::class,
        EventImagesImportedFromUDB2::class,
        EventImagesUpdatedFromUDB2::class,
        EventCopied::class,
        PlaceImageAdded::class,
        PlaceImageRemoved::class,
        PlaceImagesImportedFromUDB2::class,
        PlaceImagesUpdatedFromUDB2::class,
        OrganizerImageAdded::class,
        OrganizerImageRemoved::class,
    ];

    private Connection $connection;

    private Serializer $payloadSerializer;

    private MediaObjectRelationsProjector $projector;

    public function __construct(
        Connection $connection,
        Serializer $payloadSerializer,
        MediaObjectRelationsProjector $projector
    ) {
        parent::__construct();
        $this->connection = $connection;
        $this->payloadSerializer = $payloadSerializer;
        $this->projector = $projector;
    }

    protected function configure(): void
    {
        $this->setName('media:fill-relations')
            ->setDescription('Fills the media_object_relations table based on the image events inside the event store.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = $this->getEventsCount();

        if ($count === 0) {
            $output->writeln('No image events found.');
            return 0;
        }

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Process ' . $count . ' image event(s)? [y/N] ', false);
        if (!$helper->ask($input, $output, $question)) {
            return 0;
        }

        $progressBar = new ProgressBar($output, $count);

        $lastId = 0;
        do {
            $rows = $this->getEvents($lastId);

            foreach ($rows as $row) {
                $this->projector->handle(
                    new DomainMessage(
                        $row['uuid'],
                        (int) $row['playhead'],
                        new Metadata(),
                        $this->payloadSerializer->deserialize(Json::decodeAssociatively($row['payload'])),
                        DateTime::fromString($row['recorded_on'])
                    )
                );

                $lastId = (int) $row['id'];
                $progressBar->advance();
            }
        } while (count($rows) === self::MAX_RESULTS);

        $progressBar->finish();
        $output->writeln('');

        return 0;
    }

    private function getEvents(int $lastId): array
    {
        return $this->connection->createQueryBuilder()
            ->select('id', 'uuid', 'playhead', 'payload', 'recorded_on')
            ->from('event_store')
            ->where('id > :lastId')
            ->andWhere('type IN (:types)')
            ->setParameter('lastId', $lastId)
            ->setParameter('types', $this->getEventTypes(), Connection::PARAM_STR_ARRAY)
            ->orderBy('id', 'ASC')
            ->setMaxResults(self::MAX_RESULTS)
            ->execute()
            ->fetchAllAssociative();
    }

    private function getEventsCount(): int
    {
        return (int) $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('event_store')
            ->where('type IN (:types)')
            ->setParameter('types', $this->getEventTypes(), Connection::PARAM_STR_ARRAY)
            ->execute()
            ->fetchOne();
    }

    /**
     * The type column contains the class name of the event, with dots instead of backslashes.
     */
    private function getEventTypes(): array
    {
        return array_map(
            fn (string $class) => str_replace('\\', '.', $class),
            self::EVENT_CLASSES
        );
    }
}
