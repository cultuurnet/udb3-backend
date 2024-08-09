<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class UpdateUniqueLabels extends Command
{
    private const MAX_RESULTS = 1000;
    private const LABEL_CREATED = 'CultuurNet.UDB3.Label.Events.Created';

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    protected function configure(): void
    {
        $this->setName('label:update-unique')
            ->setDescription('Updates the table with unique labels based on the `CultuurNet.UDB3.Label.Events.Created` events inside the event store.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $labelAddedEventsCount = $this->getAllLabelAddedEventsCount();

        if ($labelAddedEventsCount <= 0) {
            $output->writeln('No `LabelAdded` events found.');
            return 0;
        }

        $helper = $this->getHelper('question');
        $updateQuestion = new ConfirmationQuestion('Update ' . $labelAddedEventsCount . ' label(s)? [y/N] ', false);
        if (!$helper->ask($input, $output, $updateQuestion)) {
            return 0;
        }

        $progressBar = new ProgressBar($output, $labelAddedEventsCount);

        $messages = [];
        $offset = 0;
        do {
            $labelAddedEvents = $this->getAllLabelAddedEvents($offset);

            foreach ($labelAddedEvents as $labelAddedEvent) {
                $labelUuid = $this->getLabelUuid($labelAddedEvent);
                $labelName = $this->getLabelName($labelAddedEvent);

                try {
                    $this->updateLabel($labelUuid, $labelName);
                    $messages[] = 'Added label ' . $labelName->toString() . ' with uuid ' . $labelUuid->toString();
                } catch (UniqueConstraintViolationException $exception) {
                    $messages[] = 'Unique exception for label ' . $labelName->toString() . ' with uuid ' . $labelUuid->toString();
                }

                $progressBar->advance();
            }

            $offset += count($labelAddedEvents);
        } while ($offset < $labelAddedEventsCount);

        $progressBar->finish();
        $output->writeln('');

        $reportQuestion = new ConfirmationQuestion('Dump update report? [y/N] ', false);
        if (!$helper->ask($input, $output, $reportQuestion)) {
            return 0;
        }

        $reportFile = fopen('update_unique_labels_report.txt', 'wb');
        foreach ($messages as $message) {
            fwrite($reportFile, $message . \PHP_EOL);
        }
        \fclose($reportFile);

        return 0;
    }

    private function getAllLabelAddedEvents(int $offset): array
    {
        return $this->connection->createQueryBuilder()
            ->select('uuid, payload')
            ->from('event_store')
            ->where('type = "' . self::LABEL_CREATED . '"')
            ->setFirstResult($offset)
            ->setMaxResults(self::MAX_RESULTS)
            ->execute()
            ->fetchAllAssociative();
    }

    private function getAllLabelAddedEventsCount(): int
    {
        return count(
            $this->connection->createQueryBuilder()
                ->select('uuid')
                ->from('event_store')
                ->where('type = "' . self::LABEL_CREATED . '"')
                ->orderBy('id')
                ->execute()
                ->fetchAllAssociative()
        );
    }

    /**
     * @throws DBALException
     * @throws UniqueConstraintViolationException
     */
    private function updateLabel(UuidInterface $labelUuid, LabelName $labelName): void
    {
        $this->connection
            ->insert(
                'labels_unique',
                [
                    'uuid_col' => $labelUuid->toString(),
                    'unique_col' => $labelName->toString(),
                ]
            );
    }

    private function getLabelUuid(array $labelAddedEvent): UuidInterface
    {
        return Uuid::fromString($labelAddedEvent['uuid']);
    }

    private function getLabelName(array $labelAddedEvent): LabelName
    {
        $payloadArray = Json::decodeAssociatively($labelAddedEvent['payload']);
        return new LabelName($payloadArray['payload']['name']);
    }
}
