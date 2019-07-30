<?php

namespace CultuurNet\UDB3\UiTPAS\Event;

use Broadway\CommandHandling\CommandBusInterface;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\Event\Commands\AddLabel;
use CultuurNet\UDB3\Event\Commands\RemoveLabel;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Offer\Commands\AbstractLabelCommand;
use CultuurNet\UDB3\UiTPAS\Event\Event\EventCardSystemsUpdated;
use CultuurNet\UDB3\UiTPAS\Label\UiTPASLabelsRepositoryInterface;
use Psr\Log\LoggerInterface;

class EventProcessManager implements EventListenerInterface
{
    /**
     * @var DocumentRepositoryInterface
     */
    private $eventDocumentRepository;

    /**
     * @var CommandBusInterface
     */
    private $commandBus;

    /**
     * @var UiTPASLabelsRepositoryInterface
     */
    private $uitpasLabelsRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param DocumentRepositoryInterface $eventDocumentRepository
     * @param CommandBusInterface $commandBus
     * @param UiTPASLabelsRepositoryInterface $uitpasLabelsRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        DocumentRepositoryInterface $eventDocumentRepository,
        CommandBusInterface $commandBus,
        UiTPASLabelsRepositoryInterface $uitpasLabelsRepository,
        LoggerInterface $logger
    ) {
        $this->eventDocumentRepository = $eventDocumentRepository;
        $this->commandBus = $commandBus;
        $this->uitpasLabelsRepository = $uitpasLabelsRepository;
        $this->logger = $logger;
    }

    /**
     * @param DomainMessage $domainMessage
     *
     * @uses handleEventCardSystemsUpdated
     */
    public function handle(DomainMessage $domainMessage)
    {
        $map = [
            EventCardSystemsUpdated::class => 'handleEventCardSystemsUpdated',
        ];

        $payload = $domainMessage->getPayload();
        $className = get_class($payload);
        if (isset($map[$className])) {
            $handlerMethodName = $map[$className];
            call_user_func([$this, $handlerMethodName], $payload);
        }
    }

    /**
     * @param EventCardSystemsUpdated $eventCardSystemsUpdated
     */
    private function handleEventCardSystemsUpdated(EventCardSystemsUpdated $eventCardSystemsUpdated)
    {
        $eventId = $eventCardSystemsUpdated->getId()->toNative();

        $this->logger->info('Handling updated card systems message for event ' . $eventId);

        $uitpasLabels = $this->uitpasLabelsRepository->loadAll();

        // Simply remove all UiTPAS labels from the event, even if they're
        // found on the JSON-LD or not. This is the best way to make sure
        // there are no UiTPAS labels on the event, and the aggregate will
        // just ignore the commands if the labels are not present anyway.
        // Even if the UiTPAS labels are added again from the organizer.
        // Otherwise we would have to check the event has UiTPAS labels
        // which are not present on the organizer.
        $this->logger->info('Removing all UiTPAS labels from event ' . $eventId);
        $this->removeLabelsFromEvent($eventId, $uitpasLabels);

        if ($eventCardSystemsUpdated->getCardSystems()->length() > 0) {
            $this->logger->info('Inheriting UiTPAS labels from organizer on event ' . $eventId);
            $this->copyMatchingLabelsFromOrganizerToEvent($eventId, $uitpasLabels);
        }
    }

    /**
     * @param string $eventId
     * @param Label[] $labels
     */
    private function removeLabelsFromEvent($eventId, array $labels)
    {
        $commands = array_map(
            function (Label $label) use ($eventId) {
                return new RemoveLabel(
                    $eventId,
                    $label
                );
            },
            $labels
        );

        $this->dispatchCommands($commands);
    }

    /**
     * @param string $eventId
     * @param Label[] $potentialLabelsToCopy
     */
    private function copyMatchingLabelsFromOrganizerToEvent($eventId, array $potentialLabelsToCopy)
    {
        $eventDocument = $this->eventDocumentRepository->get($eventId);
        if (!$eventDocument) {
            $this->logger->error('Event with id ' . $eventId . ' not found in injected DocumentRepository!');
            return;
        }

        $jsonLD = $eventDocument->getBody();
        if (!isset($jsonLD->organizer)) {
            $this->logger->error('Found no organizer on event ' . $eventId);
            return;
        }

        $organizerLabels = isset($jsonLD->organizer->labels) ? $jsonLD->organizer->labels : [];
        $this->logger->info(
            'Found organizer labels on event ' . $eventId . ': ' . implode(', ', $organizerLabels)
        );

        $hiddenOrganizerLabels = isset($jsonLD->organizer->hiddenLabels) ? $jsonLD->organizer->hiddenLabels : [];
        $this->logger->info(
            'Found hidden organizer labels on event ' . $eventId . ': ' . implode(', ', $hiddenOrganizerLabels)
        );

        $potentialLabelsToCopyAsString = array_map(
            function (Label $label) {
                return (string) $label;
            },
            $potentialLabelsToCopy
        );

        $this->addIntersectingLabelsToEvent($eventId, $potentialLabelsToCopy, $organizerLabels, true);
        $this->addIntersectingLabelsToEvent($eventId, $potentialLabelsToCopy, $hiddenOrganizerLabels, false);
    }

    /**
     * @param string $eventId
     * @param Label[] $labels1
     * @param string[] $labels2
     * @param bool $visible
     */
    private function addIntersectingLabelsToEvent($eventId, array $labels1, $labels2, $visible)
    {
        $matchingLabels = [];
        foreach ($labels1 as $label1) {
            foreach ($labels2 as $label2) {
                if ($label1->equals(new Label($label2))) {
                    $matchingLabels[] = $label1;
                    break;
                }
            }
        }

        $this->logger->info(
            'Found uitpas organizer labels on event ' . $eventId . ': ' . implode(', ', $matchingLabels)
        );

        $commands = array_map(
            function (Label $matchingLabel) use ($eventId, $visible) {
                return new AddLabel(
                    $eventId,
                    new Label((string) $matchingLabel, $visible)
                );
            },
            $matchingLabels
        );

        $this->dispatchCommands($commands);
    }

    /**
     * @param AbstractLabelCommand[] $commands
     */
    private function dispatchCommands($commands)
    {
        foreach ($commands as $command) {
            $this->logger->info(
                'Dispatching label command ' . get_class($command),
                [
                    'item id' => $command->getItemId(),
                    'label' => (string) $command->getLabel(),
                ]
            );

            $this->commandBus->dispatch($command);
        }
    }
}
