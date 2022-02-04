<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\Broadway\AMQP\AMQPPublisher;
use CultuurNet\UDB3\EventSourcing\DomainMessageBuilder;
use CultuurNet\UDB3\ReadModel\DocumentEventFactory;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Knp\Command\Command;
use Ramsey\Uuid\UuidFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class ReindexEventsWithRecommendations extends Command
{
    private Connection $connection;

    private AMQPPublisher $amqpPublisher;

    private DocumentEventFactory $eventFactoryForEvents;

    public function __construct(
        Connection $connection,
        AMQPPublisher $amqpPublisher,
        DocumentEventFactory $eventFactoryForEvents
    ) {
        $this->connection = $connection;
        $this->amqpPublisher = $amqpPublisher;
        $this->eventFactoryForEvents = $eventFactoryForEvents;

        // It's important to call the parent constructor after setting the properties.
        // Because the parent constructor calls the `configure` method.
        // In this command the command name is created dynamically with the type property.
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('event:reindex-events-with-recommendations')
            ->setDescription('Reindex events that have recommendations.')
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Skip confirmation.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $eventIds = $this->getRecommendedEventIds();
        if (count($eventIds) < 1) {
            $output->writeln('No recommended events found.');
            return 0;
        }

        if (!$this->askConfirmation($input, $output, count($eventIds))) {
            return 0;
        }

        foreach ($eventIds as $eventId) {
            $this->handleEvent($eventId);
        }

        return 0;
    }

    private function getRecommendedEventIds(): array
    {
        return $this->connection->createQueryBuilder()
            ->select('DISTINCT recommended_event_id')
            ->from('event_recommendations')
            ->execute()
            ->fetchAll(FetchMode::COLUMN);
    }

    private function askConfirmation(InputInterface $input, OutputInterface $output, int $count): bool
    {
        if ($input->getOption('force')) {
            return true;
        }

        return $this
            ->getHelper('question')
            ->ask(
                $input,
                $output,
                new ConfirmationQuestion('Reindex ' . $count . ' events? [y/N] ', false)
            );
    }

    private function handleEvent(string $eventId): void
    {
        $projectedEvent = $this->eventFactoryForEvents->createEvent($eventId);

        $this->amqpPublisher->handle(
            (new DomainMessageBuilder(new UuidFactory()))->create($projectedEvent)
        );
    }
}
