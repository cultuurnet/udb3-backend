<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\Domain\DateTime;
use CultuurNet\UDB3\AggregateType;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\RecordedOn;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class FindOutOfSyncProjections extends Command
{
    private Connection $connection;
    private DocumentRepository $eventDocumentRepository;
    private DocumentRepository $placeDocumentRepository;
    private DocumentRepository $organizerDocumentRepository;

    public function __construct(
        Connection $connection,
        DocumentRepository $eventDocumentRepository,
        DocumentRepository $placeDocumentRepository,
        DocumentRepository $organizerDocumentRepository
    ) {
        $this->connection = $connection;
        $this->eventDocumentRepository = $eventDocumentRepository;
        $this->placeDocumentRepository = $placeDocumentRepository;
        $this->organizerDocumentRepository = $organizerDocumentRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $aggregateTypeEnumeration = implode(
            ', ',
            AggregateType::getAllowedValues()
        );

        $this
            ->setName('find-out-of-sync-projections')
            ->setDescription('Find projections that are behind with the events inside the event store')
            ->addArgument(
                'aggregate-type',
                InputArgument::REQUIRED,
                'Aggregate type to find missing projections. One of: ' . $aggregateTypeEnumeration . '.',
            )
            ->addArgument(
                'first-id',
                InputArgument::REQUIRED,
                'Id of the first row to process.',
            )
            ->addArgument(
                'last-id',
                InputArgument::REQUIRED,
                'Id of the last row to process.',
            );
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $aggregateType = new AggregateType($input->getArgument('aggregate-type'));

        $results = $this->connection->createQueryBuilder()
            ->select('uuid', 'MAX(recorded_on) as recorded')
            ->from('event_store')
            ->where('aggregate_type = :aggregate_type')
            ->setParameter(':aggregate_type', $aggregateType->toString())
            ->andWhere('id >= :first_id')
            ->setParameter(':first_id', $input->getArgument('first-id'))
            ->andWhere('id <= :last_id')
            ->setParameter(':last_id', $input->getArgument('last-id'))
            ->groupBy('uuid')
            ->execute()
            ->fetchAll();

        foreach ($results as $result) {
            $uuid = $result['uuid'];
            $recorded = $result['recorded'];
            $modified = $this->getModified($uuid, $aggregateType, $output);

            if ($modified) {
                $recordedDate = RecordedOn::fromBroadwayDateTime(DateTime::fromString($recorded));
                $modifiedDate = RecordedOn::fromBroadwayDateTime(DateTime::fromString($modified));

                if ($recordedDate->toString() !== $modifiedDate->toString()) {
                    $output->writeln($uuid . ' projection of is out of sync');
                }
            }
        }

        return 0;
    }

    private function getModified(string $uuid, AggregateType $aggregateType, OutputInterface $output): ?string
    {
        $jsonLd = null;

        try {
            if ($aggregateType->sameAs(AggregateType::event())) {
                $jsonLd = $this->eventDocumentRepository->fetch($uuid);
            }
            if ($aggregateType->sameAs(AggregateType::place())) {
                $jsonLd = $this->placeDocumentRepository->fetch($uuid);
            }
            if ($aggregateType->sameAs(AggregateType::organizer())) {
                $jsonLd = $this->organizerDocumentRepository->fetch($uuid);
            }
        } catch (DocumentDoesNotExist $e) {
            $output->writeln($uuid . ' - no jsonLd found');
            return null;
        }

        $body = $jsonLd->getAssocBody();
        return $body['modified'];
    }
}
