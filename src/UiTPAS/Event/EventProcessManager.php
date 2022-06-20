<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\Event;

use Broadway\CommandHandling\CommandBus;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;
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
     */
    private function labelsContain(array $haystack, Label $needle): bool
    {
        foreach ($haystack as $label) {
            if ($needle->getName()->sameAs($label->getName())) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $eventId
     * @param Label[] $labels
     */
    private function removeLabelsFromEvent($eventId, array $labels): void
    {
        $this->logger->info(
            'Removing UiTPAS labels for irrelevant card systems from event ' . $eventId . ' (if applied)'
        );

        $commands = array_map(
            function (Label $label) use ($eventId) {
                return new RemoveLabel(
                    $eventId,
                    $label->getName()->toString(),
                    $label->isVisible()
                );
            },
            $labels
        );

        $this->dispatchCommands($commands);
    }

    /**
     * @param Label[] $labels
     */
    private function addLabelsToEvent(string $eventId, array $labels): void
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
     * @param AbstractCommand[] $commands
     */
    private function dispatchCommands($commands): void
    {
        foreach ($commands as $command) {
            if ($command instanceof AddLabel) {
                $labelName = $command->getLabel()->getName()->toString();
            } elseif ($command instanceof RemoveLabel) {
                $labelName = $command->getLabelName();
            } else {
                return;
            }
            $this->logger->info(
                'Dispatching label command ' . get_class($command),
                [
                    'item id' => $command->getItemId(),
                    'label' => $labelName,
                ]
            );

            $this->commandBus->dispatch($command);
        }
    }
}
