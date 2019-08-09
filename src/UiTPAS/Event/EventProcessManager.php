<?php

namespace CultuurNet\UDB3\UiTPAS\Event;

use Broadway\CommandHandling\CommandBusInterface;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\Event\Commands\AddLabel;
use CultuurNet\UDB3\Event\Commands\RemoveLabel;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Offer\Commands\AbstractLabelCommand;
use CultuurNet\UDB3\UiTPAS\CardSystem\CardSystem;
use CultuurNet\UDB3\UiTPAS\Event\Event\EventCardSystemsUpdated;
use CultuurNet\UDB3\UiTPAS\Label\UiTPASLabelsRepository;
use Psr\Log\LoggerInterface;

class EventProcessManager implements EventListenerInterface
{
    /**
     * @var CommandBusInterface
     */
    private $commandBus;

    /**
     * @var UiTPASLabelsRepository
     */
    private $uitpasLabelsRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CommandBusInterface $commandBus
     * @param UiTPASLabelsRepository $uitpasLabelsRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        CommandBusInterface $commandBus,
        UiTPASLabelsRepository $uitpasLabelsRepository,
        LoggerInterface $logger
    ) {
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

        $uitPasLabels = $this->uitpasLabelsRepository->loadAll();

        $expectedUitPasLabelsForEvent = array_map(
            function (CardSystem $cardSystem) use ($uitPasLabels, $eventId) {
                $id = $cardSystem->getId()->toNative();

                if (isset($uitPasLabels[$id])) {
                    return $uitPasLabels[$id];
                } else {
                    $this->logger->warning(
                        'Could not find UiTPAS label for card system ' . $id . ' on event ' . $eventId
                    );
                    return null;
                }
            },
            $eventCardSystemsUpdated->getCardSystems()->toArray()
        );
        $expectedUitPasLabelsForEvent = array_filter($expectedUitPasLabelsForEvent);

        $uitPasLabelsToRemoveIfApplied = [];
        foreach ($uitPasLabels as $uitPasLabel) {
            $remove = true;

            foreach ($expectedUitPasLabelsForEvent as $expectedUitPasLabelForEvent) {
                if ($uitPasLabel->equals($expectedUitPasLabelForEvent)) {
                    $remove = false;
                    break;
                }
            }

            if ($remove) {
                $uitPasLabelsToRemoveIfApplied[] = $uitPasLabel;
            }
        }

        $this->logger->info(
            'Removing UiTPAS labels for irrelevant card systems from event ' . $eventId . ' (if applied)'
        );
        $this->removeLabelsFromEvent($eventId, $uitPasLabelsToRemoveIfApplied);

        if ($eventCardSystemsUpdated->getCardSystems()->length() > 0) {
            $this->logger->info(
                'Adding UiTPAS labels for active card systems on event ' . $eventId . '(if not applied yet)'
            );
            $this->addLabelsToEvent($eventId, $expectedUitPasLabelsForEvent);
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
     * @param Label[] $labels
     */
    private function addLabelsToEvent($eventId, array $labels)
    {
        $commands = array_map(
            function (Label $label) use ($eventId) {
                return new AddLabel(
                    $eventId,
                    $label
                );
            },
            $labels
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
