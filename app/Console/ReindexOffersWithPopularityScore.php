<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\Domain\DomainEventStream;
use CultuurNet\Broadway\EventHandling\ReplayModeEventBusInterface;
use CultuurNet\UDB3\EventSourcing\DomainMessageBuilder;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\ReadModel\DocumentEventFactory;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ReindexOffersWithPopularityScore extends Command
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var ReplayModeEventBusInterface
     */
    private $eventBus;

    /**
     * @var DocumentEventFactory
     */
    private $eventFactoryForEvents;

    public function __construct(
        OfferType $type,
        Connection $connection,
        ReplayModeEventBusInterface $eventBus,
        DocumentEventFactory $eventFactoryForEvents
    ) {
        $this->type = \strtolower($type->toNative());
        $this->connection = $connection;
        $this->eventBus = $eventBus;
        $this->eventFactoryForEvents = $eventFactoryForEvents;

        // It's important to call the parent constructor after setting the properties.
        // Because the parent constructor calls the `configure` method.
        // In this command the command name is created dynamically with the type property.
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName($this->type.':reindex-offers-with-popularity')
            ->setDescription('Reindex events or places that have a popularity score.')
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Skip confirmation.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $offerIds = $this->getOfferIds($this->type);
        if (count($offerIds) < 1) {
            $output->writeln('No ' . $this->type . 's found with a popularity.');
            return 0;
        }

        if (!$this->askConfirmation($input, $output, $this->type, count($offerIds))) {
            return 0;
        }

        $this->eventBus->startReplayMode();

        foreach ($offerIds as $offerId) {
            $this->dispatchEvent($offerId);
        }

        $this->eventBus->stopReplayMode();

        return 0;
    }

    private function getOfferIds(string $type): array
    {
        return $this->connection->createQueryBuilder()
            ->select('offer_id')
            ->from('offer_popularity')
            ->where('offer_type = :type')
            ->setParameter(':type', $type)
            ->execute()
            ->fetchAll(FetchMode::COLUMN);
    }

    private function askConfirmation(InputInterface $input, OutputInterface $output, string $type, int $count): bool
    {
        if ($input->getOption('force')) {
            return true;
        }

        return $this
            ->getHelper('question')
            ->ask(
                $input,
                $output,
                new ConfirmationQuestion('Reindex ' . $count . ' ' . $type . 's? [y/N] ', false)
            );
    }

    private function dispatchEvent(string $id): void
    {
        $projectedEvent = $this->eventFactoryForEvents->createEvent($id);

        $this->eventBus->publish(
            new DomainEventStream([
                (new DomainMessageBuilder())->create($projectedEvent),
            ])
        );
    }
}
