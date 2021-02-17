<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PDO;
use Rhumsaa\Uuid\Uuid;
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
            ->setDescription('Updates the table with organizer unique websites based on the `OrganizerCreated` events inside the event store.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $organizerCreatedEventsCount = $this->getAllOrganizerCreatedEventsCount();

        if ($organizerCreatedEventsCount <= 0) {
            $output->writeln('No `OrganizerCreated` events found.');
            return 0;
        }

        $helper = $this->getHelper('question');
        $updateQuestion = new ConfirmationQuestion('Update ' . $organizerCreatedEventsCount . ' organizer(s)? [y/N] ', false);
        if (!$helper->ask($input, $output, $updateQuestion)) {
            return 0;
        }

        $progressBar = new ProgressBar($output, $organizerCreatedEventsCount);

        $messages = [];
        $offset = 0;
        do {
            $organizerCreatedEvents = $this->getAllOrganizerCreatedEvents($offset);

            foreach ($organizerCreatedEvents as $organizerCreatedEvent) {
                $organizerUuid = $this->getOrganizerUuid($organizerCreatedEvent);
                $organizerUrl = $this->getOrganizerWebsite($organizerCreatedEvent);

                try {
                    $this->updateOrganizer($organizerUuid, $organizerUrl);
                    $messages[] = 'Added organizer ' . $organizerUrl . ' with uuid ' . $organizerUuid->toString();
                } catch (UniqueConstraintViolationException $exception) {
                    $messages[] = 'Unique exception for organizer ' . $organizerUrl . ' with uuid ' . $organizerUuid->toString();
                }

                $progressBar->advance();
            }

            $offset += count($organizerCreatedEvents);
        } while ($offset < $organizerCreatedEventsCount);

        $progressBar->finish();
        $output->writeln('');

        $reportQuestion = new ConfirmationQuestion('Dump update report? [y/N] ', false);
        if (!$helper->ask($input, $output, $reportQuestion)) {
            return 0;
        }

        $reportFile = fopen('update_unique_organizers_report.txt', 'wb');
        foreach ($messages as $message) {
            fwrite($reportFile, $message. \PHP_EOL);
        }
        \fclose($reportFile);

        return 0;
    }

    private function getAllOrganizerCreatedEvents(int $offset): array
    {
        return $this->connection->createQueryBuilder()
            ->select('uuid, payload')
            ->from('event_store')
            ->where('type = "' . self::ORGANIZER_CREATED . '"')
            ->setFirstResult($offset)
            ->setMaxResults(self::MAX_RESULTS)
            ->execute()
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getAllOrganizerCreatedEventsCount(): int
    {
        return $this->connection->createQueryBuilder()
            ->select('uuid')
            ->from('event_store')
            ->where('type = "' . self::ORGANIZER_CREATED . '"')
            ->execute()
            ->rowCount();
    }

    /**
     * @throws DBALException
     * @throws UniqueConstraintViolationException
     */
    private function updateOrganizer(Uuid $organizerUuid, Url $organizerUrl): void
    {
        $this->connection
            ->insert(
                'organizer_unique_websites',
                [
                    'uuid_col' => $organizerUuid->toString(),
                    'unique_col' => (string) $organizerUrl,
                ]
            );
    }

    private function getOrganizerUuid(array $organizerCreatedEvent): Uuid
    {
        return Uuid::fromString($organizerCreatedEvent['uuid']);
    }

    private function getOrganizerWebsite(array $organizerCreatedEvent): Url
    {
        $payloadArray = json_decode($organizerCreatedEvent['payload'], true);
        return Url::fromNative($payloadArray['payload']['website']);
    }
}
