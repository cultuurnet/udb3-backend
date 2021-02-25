<?php

namespace CultuurNet\UDB3\UiTPAS\Event;

use Broadway\CommandHandling\CommandBus;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Offer\Commands\AbstractLabelCommand;
use CultuurNet\UDB3\Offer\Commands\AddLabel;
use CultuurNet\UDB3\Offer\Commands\RemoveLabel;
use CultuurNet\UDB3\UiTPAS\CardSystem\CardSystem;
use CultuurNet\UDB3\UiTPAS\Event\Event\EventCardSystemsUpdated;
use CultuurNet\UDB3\UiTPAS\Label\UiTPASLabelsRepository;
use Psr\Log\LoggerInterface;

class EventProcessManager implements EventListener
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var UiTPASLabelsRepository
     */
    private $uitPasLabelsRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        CommandBus $commandBus,
        UiTPASLabelsRepository $uitPasLabelsRepository,
        LoggerInterface $logger
    ) {
        $this->commandBus = $commandBus;
        $this->uitPasLabelsRepository = $uitPasLabelsRepository;
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

        $uitPasLabels = $this->uitPasLabelsRepository->loadAll();

        $applicableLabelsForEvent = $this->determineApplicableLabelsForEvent(
            $eventCardSystemsUpdated->getCardSystems(),
            $uitPasLabels
        );

        $inapplicableLabelsForEvent = $this->determineInapplicableLabelsForEvent(
            $applicableLabelsForEvent,
            $uitPasLabels
        );

        // Dispatch commands to remove the labels that are not supposed to be on the event.
        // The event aggregate will check if the label is present and only record a LabelRemoved event if it was.
        $this->removeLabelsFromEvent($eventId, $inapplicableLabelsForEvent);

        // Dispatch commands to add the labels that are supposed to be on the event.
        // The event aggregate will check if the label is present and only record a LabelAdded event if it was not.
        $this->addLabelsToEvent($eventId, $applicableLabelsForEvent);
    }

    /**
     * @param CardSystem[] $cardSystems
     * @param Label[] $uitPasLabels
     * @return Label[]
     */
    private function determineApplicableLabelsForEvent(
        array $cardSystems,
        array $uitPasLabels
    ): array {
        $convertCardSystemToLabel = function (CardSystem $cardSystem) use ($uitPasLabels) {
            $cardSystemId = $cardSystem->getId()->toNative();

            if (!isset($uitPasLabels[$cardSystemId])) {
                $this->logger->warning(
                    'Could not find UiTPAS label for card system ' . $cardSystemId
                );
                return null;
            }

            return $uitPasLabels[$cardSystemId];
        };

        return array_filter(
            array_map($convertCardSystemToLabel, $cardSystems)
        );
    }

    /**
     * @param Label[] $applicableLabels
     * @param Label[] $uitPasLabels
     * @return Label[]
     */
    private function determineInapplicableLabelsForEvent(
        array $applicableLabels,
        array $uitPasLabels
    ): array {
        $uitPasLabelDoesNotApply = function (Label $uitPasLabel) use ($applicableLabels) {
            return !$this->labelsContain($applicableLabels, $uitPasLabel);
        };

        return array_filter(
            $uitPasLabels,
            $uitPasLabelDoesNotApply
        );
    }

    /**
     * @param Label[] $haystack
     * @param Label $needle
     * @return bool
     */
    private function labelsContain(array $haystack, Label $needle): bool
    {
        foreach ($haystack as $label) {
            if ($needle->equals($label)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $eventId
     * @param Label[] $labels
     */
    private function removeLabelsFromEvent($eventId, array $labels)
    {
        $this->logger->info(
            'Removing UiTPAS labels for irrelevant card systems from event ' . $eventId . ' (if applied)'
        );

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
        if (count($labels) === 0) {
            return;
        }

        $this->logger->info(
            'Adding UiTPAS labels for active card systems on event ' . $eventId . '(if not applied yet)'
        );

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
