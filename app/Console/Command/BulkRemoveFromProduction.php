<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\Domain\DomainEventStream;
use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Event\Productions\ProductionId;
use CultuurNet\UDB3\Event\Productions\ProductionRepository;
use CultuurNet\UDB3\EventSourcing\DomainMessageBuilder;
use CultuurNet\UDB3\ReadModel\DocumentEventFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class BulkRemoveFromProduction extends Command
{
    private ProductionRepository $repository;

    private EventBus $eventBus;

    private DocumentEventFactory $eventFactory;

    public function __construct(
        ProductionRepository $repository,
        EventBus $eventBus,
        DocumentEventFactory $eventFactory
    ) {
        $this->repository = $repository;
        $this->eventBus = $eventBus;
        $this->eventFactory = $eventFactory;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('event:bulk-remove-from-production')
            ->setDescription('Bulk removes events from a production')
            ->addArgument(
                'productionId',
                InputOption::VALUE_REQUIRED,
                'The id of the production contains incorrect events.'
            )
            ->addOption(
                'eventId',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'An array of eventIds to remove from the production.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $productionId = ProductionId::fromNative($input->getArgument('productionId'));
        try {
            $production = $this->repository->find($productionId);
        } catch (EntityNotFoundException $exception) {
            $output->writeln($exception->getMessage());
            return 1;
        }

        $originalEventIds = $production->getEventIds();
        $eventIdsToRemove = $input->getOption('eventId');

        if (!empty(array_diff($eventIdsToRemove, $originalEventIds))) {
            $output->writeln('The input contains events which are not part of the production with id ' . $productionId->toNative());
            return 1;
        }

        foreach ($eventIdsToRemove as $eventIdToRemove) {
            $this->repository->removeEvent($eventIdToRemove, $productionId);
        }

        $this->dispatchEventsProjectedToJsonLd(...$originalEventIds);

        $domainMessages = [];
        foreach ($originalEventIds as $originalEventId) {
            $eventProjectedToJsonLd = $this->eventFactory->createEvent($originalEventId);
            $domainMessages[] = (new DomainMessageBuilder())->create($eventProjectedToJsonLd);
        }
        $stream = new DomainEventStream($domainMessages);
        $this->eventBus->publish($stream);

        return 0;
    }

    private function dispatchEventsProjectedToJsonLd(string ...$eventIds): void
    {
        $domainMessages = [];
        foreach ($eventIds as $eventId) {
            $eventProjectedToJsonLd = $this->eventFactory->createEvent($eventId);
            $domainMessages[] = (new DomainMessageBuilder())->create($eventProjectedToJsonLd);
        }
        $stream = new DomainEventStream($domainMessages);
        $this->eventBus->publish($stream);
    }
}
