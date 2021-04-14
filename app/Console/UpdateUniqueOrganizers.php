<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PDO;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use ValueObjects\Web\Url;

class UpdateUniqueOrganizers extends Command
{
    private const MAX_RESULTS = 1000;
    private const ORGANIZER_CREATED = 'CultuurNet.UDB3.Organizer.Events.OrganizerCreatedWithUniqueWebsite';
    private const ORGANIZER_WEBSITE_UPDATED = 'CultuurNet.UDB3.Organizer.Events.WebsiteUpdated';

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    protected function configure(): void
    {
        $this->setName('organizer:update-unique')
            ->setDescription('Updates the table with organizer unique websites based on the `OrganizerCreated` and `WebsiteUpdated` events inside the event store.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $organizerEventsCount = $this->getAllOrganizerEventsCount();

        if ($organizerEventsCount <= 0) {
            $output->writeln('No `OrganizerCreated` or `WebsiteUpdated` events found.');
            return 0;
        }

        $helper = $this->getHelper('question');
        $updateQuestion = new ConfirmationQuestion('Update ' . $organizerEventsCount . ' organizer(s)? [y/N] ', false);
        if (!$helper->ask($input, $output, $updateQuestion)) {
            return 0;
        }

        $progressBar = new ProgressBar($output, $organizerEventsCount);

        $messages = [];
        $offset = 0;
        do {
            $organizerEvents = $this->getAllOrganizerEvents($offset);

            foreach ($organizerEvents as $organizerEvent) {
                $organizerUuid = $organizerEvent['uuid'];
                $organizerUrl = $this->getOrganizerWebsite($organizerEvent);

                $updated = $this->updateOrganizer($organizerUuid, $organizerUrl);
                if ($updated) {
                    $messages[] = 'Added/updated organizer ' . $organizerUrl . ' with uuid ' . $organizerUuid;
                } else {
                    $messages[] = 'Skipped organizer ' . $organizerUrl . ' with uuid ' . $organizerUuid;
                }

                $progressBar->advance();
            }

            $offset += count($organizerEvents);
        } while ($offset < $organizerEventsCount);

        $progressBar->finish();
        $output->writeln('');

        $reportQuestion = new ConfirmationQuestion('Dump update report? [y/N] ', false);
        if (!$helper->ask($input, $output, $reportQuestion)) {
            return 0;
        }

        $reportFile = fopen('update_unique_organizers_report.txt', 'wb');
        foreach ($messages as $message) {
            fwrite($reportFile, $message . PHP_EOL);
        }
        fclose($reportFile);

        return 0;
    }

    private function getAllOrganizerEvents(int $offset): array
    {
        return $this->connection->createQueryBuilder()
            ->select('uuid, payload')
            ->from('event_store')
            ->where('type = "' . self::ORGANIZER_CREATED . '"')
            ->orWhere('type = "' . self::ORGANIZER_WEBSITE_UPDATED . '"')
            ->setFirstResult($offset)
            ->setMaxResults(self::MAX_RESULTS)
            ->execute()
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getAllOrganizerEventsCount(): int
    {
        return $this->connection->createQueryBuilder()
            ->select('uuid')
            ->from('event_store')
            ->where('type = "' . self::ORGANIZER_CREATED . '"')
            ->orWhere('type = "' . self::ORGANIZER_WEBSITE_UPDATED . '"')
            ->orderBy('id')
            ->execute()
            ->rowCount();
    }

    /**
     * @throws DBALException
     */
    private function updateOrganizer(string $organizerUuid, Url $organizerUrl): bool
    {
        // There are 3 possible states:
        // 1. The organizer uuid is present with the correct url
        // 2. The organizer uuid is not present
        // 3. The organizer uuid is present with another value

        $existingOrganizerUrls = $this->connection->createQueryBuilder()
            ->select('unique_col')
            ->from('organizer_unique_websites')
            ->where('uuid_col = :uuid')
            ->setParameter('uuid', $organizerUuid)
            ->execute()
            ->fetchAll(PDO::FETCH_COLUMN);

        $existingOrganizerUrl = count($existingOrganizerUrls) === 1 ? $existingOrganizerUrls[0] : null;

        if ($existingOrganizerUrl === (string) $organizerUrl) {
            return false;
        }

        if ($existingOrganizerUrl === null) {
            try {
                $this->connection
                    ->insert(
                        'organizer_unique_websites',
                        [
                            'uuid_col' => $organizerUuid,
                            'unique_col' => (string)$organizerUrl,
                        ]
                    );
                return true;
            } catch (UniqueConstraintViolationException $e) {
                // The website is already in use by another organizer.
                // This happened because the organizer_unique_websites table was truncated at some point, which caused
                // the unique check to malfunction. The goal of this script is to get the websites back into the unique
                // table regardless of their organizer id so the unique check works again, before we can start cleaning
                // up the duplicates, so we need to handle this gracefully and continue without crashing.
                return false;
            }
        }

        $this->connection
            ->update(
                'organizer_unique_websites',
                [
                    'uuid_col' => $organizerUuid,
                    'unique_col' => (string) $organizerUrl,
                ],
                [
                    'uuid_col' => $organizerUuid,
                ]
            );

        return true;
    }

    private function getOrganizerWebsite(array $organizerEvent): Url
    {
        $payloadArray = json_decode($organizerEvent['payload'], true);
        return Url::fromNative($payloadArray['payload']['website']);
    }
}
