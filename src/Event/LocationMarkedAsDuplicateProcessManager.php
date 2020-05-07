<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use Broadway\CommandHandling\CommandBusInterface;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\Event\Commands\UpdateLocation;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Offer\IriOfferIdentifier;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Events\MarkedAsDuplicate;
use CultuurNet\UDB3\Search\ResultsGeneratorInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class LocationMarkedAsDuplicateProcessManager implements EventListenerInterface, LoggerAwareInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ResultsGeneratorInterface
     */
    private $searchResultsGenerator;

    /**
     * @var CommandBusInterface
     */
    private $commandBus;

    public function __construct(
        ResultsGeneratorInterface $searchResultsGenerator,
        CommandBusInterface $commandBus
    ) {
        $this->searchResultsGenerator = $searchResultsGenerator;
        $this->commandBus = $commandBus;
        $this->logger = new NullLogger();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;

        if ($this->searchResultsGenerator instanceof LoggerAwareInterface) {
            $this->searchResultsGenerator->setLogger($logger);
        }
    }

    public function handle(DomainMessage $domainMessage): void
    {
        $domainEvent = $domainMessage->getPayload();

        // Only handle (Place)MarkedAsDuplicate events.
        if (!($domainEvent instanceof MarkedAsDuplicate)) {
            return;
        }

        $duplicatePlaceId = $domainEvent->getPlaceId();
        $canonicalPlaceId = $domainEvent->getDuplicateOf();

        $query = "location.mainId:{$duplicatePlaceId}";
        $results = $this->searchResultsGenerator->search($query);

        $commands = [];
        $skipped = [];

        /* @var IriOfferIdentifier $result */
        foreach ($results as $result) {
            if (!$result->getType()->sameValueAs(OfferType::EVENT())) {
                $skipped[] = $result->getId();
                $this->logger->warning(
                    'Skipped result with id ' . $result->getId() . ' because it\'s not an event according to the @id parser.'
                );
                continue;
            }

            // Keep all updates in-memory and dispatch them only after looping through all search results, as the
            // pagination of results gets influenced by updating events in the loop.
            $commands[] = new UpdateLocation($result->getId(), new LocationId($canonicalPlaceId));
        }

        foreach ($commands as $command) {
            $this->commandBus->dispatch($command);

            $this->logger->info(
                'Dispatched UpdateLocation for result with id ' . $command->getItemId()
            );
        }

        $updated = count($commands);
        $total = $updated + count($skipped);

        $this->logger->info('Received ' . $total . ' results from the search api.');
        $this->logger->info('Updated ' . $updated . ' events to the canonical location.');
        $this->logger->info(
            'Skipped ' . count($skipped) . ' events:' . PHP_EOL . implode(PHP_EOL, $skipped)
        );
    }
}
