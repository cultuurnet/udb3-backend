<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\Event;

use Broadway\CommandHandling\CommandBus;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Event\Commands\UpdateUiTPASPrices;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Offer\Commands\AddLabel as AddLabelToOffer;
use CultuurNet\UDB3\Offer\Commands\RemoveLabel as RemoveLabelFromOffer;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Organizer\Commands\AddLabel as AddLabelToOrganizer;
use CultuurNet\UDB3\Organizer\Commands\RemoveLabel as RemoveLabelFromOrganizer;
use CultuurNet\UDB3\UiTPAS\CardSystem\CardSystem;
use CultuurNet\UDB3\UiTPAS\Event\Event\EventCardSystemsUpdated;
use CultuurNet\UDB3\UiTPAS\Event\Event\PricesUpdated;
use CultuurNet\UDB3\UiTPAS\Event\Organizer\OrganizerCardSystemsUpdated;
use CultuurNet\UDB3\UiTPAS\Event\Place\PlaceCardSystemsUpdated;
use CultuurNet\UDB3\UiTPAS\Label\UiTPASLabelsRepository;
use Psr\Log\LoggerInterface;

class EventProcessManager implements EventListener
{
    private CommandBus $commandBus;

    private UiTPASLabelsRepository $uitPasLabelsRepository;

    private LoggerInterface $logger;

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
     * @uses handleEventCardSystemsUpdated
     * @uses handlePlaceCardSystemsUpdated
     * @uses handleOrganizerCardSystemsUpdated
     * @uses handleUiTPASPricesUpdated
     */
    public function handle(DomainMessage $domainMessage): void
    {
        $map = [
            EventCardSystemsUpdated::class => 'handleEventCardSystemsUpdated',
            PlaceCardSystemsUpdated::class => 'handlePlaceCardSystemsUpdated',
            OrganizerCardSystemsUpdated::class => 'handleOrganizerCardSystemsUpdated',
            PricesUpdated::class => 'handleUiTPASPricesUpdated',
        ];

        $payload = $domainMessage->getPayload();
        $className = get_class($payload);
        if (isset($map[$className])) {
            $handlerMethodName = $map[$className];
            call_user_func([$this, $handlerMethodName], $payload);
        }
    }


    private function handleEventCardSystemsUpdated(EventCardSystemsUpdated $eventCardSystemsUpdated): void
    {
        $eventId = $eventCardSystemsUpdated->getId()->toNative();

        $this->logger->info('Handling updated card systems message for event ' . $eventId);

        $uitPasLabels = $this->uitPasLabelsRepository->loadAll();

        $applicableLabelsForEvent = $this->determineApplicableLabelsForCardSystems(
            $eventCardSystemsUpdated->getCardSystems(),
            $uitPasLabels
        );

        $inapplicableLabelsForEvent = $this->determineInapplicableLabels(
            $applicableLabelsForEvent,
            $uitPasLabels
        );

        // Dispatch commands to remove the labels that are not supposed to be on the event.
        // The event aggregate will check if the label is present and only record a LabelRemoved event if it was.
        $this->removeLabelsFromOffer(OfferType::event(), $eventId, $inapplicableLabelsForEvent);

        // Dispatch commands to add the labels that are supposed to be on the event.
        // The event aggregate will check if the label is present and only record a LabelAdded event if it was not.
        $this->addLabelsToOffer(OfferType::event(), $eventId, $applicableLabelsForEvent);
    }

    private function handlePlaceCardSystemsUpdated(PlaceCardSystemsUpdated $placeCardSystemsUpdated): void
    {
        $placeId = $placeCardSystemsUpdated->getId()->toNative();

        $this->logger->info('Handling updated card systems message for place ' . $placeId);

        $uitPasLabels = $this->uitPasLabelsRepository->loadAll();

        $applicableLabelsForPlace = $this->determineApplicableLabelsForCardSystems(
            $placeCardSystemsUpdated->getCardSystems(),
            $uitPasLabels
        );

        $inapplicableLabelsForPlace = $this->determineInapplicableLabels(
            $applicableLabelsForPlace,
            $uitPasLabels
        );

        // Dispatch commands to remove the labels that are not supposed to be on the place.
        // The place aggregate will check if the label is present and only record a LabelRemoved event if it was.
        $this->removeLabelsFromOffer(OfferType::place(), $placeId, $inapplicableLabelsForPlace);

        // Dispatch commands to add the labels that are supposed to be on the place.
        // The place aggregate will check if the label is present and only record a LabelAdded event if it was not.
        $this->addLabelsToOffer(OfferType::place(), $placeId, $applicableLabelsForPlace);
    }

    private function handleOrganizerCardSystemsUpdated(OrganizerCardSystemsUpdated $organizerCardSystemsUpdated): void
    {
        $organizerId = $organizerCardSystemsUpdated->getId()->toNative();

        $this->logger->info('Handling updated card systems message for organizer ' . $organizerId);

        $uitPasLabels = $this->uitPasLabelsRepository->loadAll();

        $applicableLabelsForPlace = $this->determineApplicableLabelsForCardSystems(
            $organizerCardSystemsUpdated->getCardSystems(),
            $uitPasLabels
        );

        $inapplicableLabelsForPlace = $this->determineInapplicableLabels(
            $applicableLabelsForPlace,
            $uitPasLabels
        );

        // Dispatch commands to remove the labels that are not supposed to be on the place.
        // The place aggregate will check if the label is present and only record a LabelRemoved event if it was.
        $this->removeLabelsFromOrganizer($organizerId, $inapplicableLabelsForPlace);

        // Dispatch commands to add the labels that are supposed to be on the place.
        // The place aggregate will check if the label is present and only record a LabelAdded event if it was not.
        $this->addLabelsToOrganizer($organizerId, $applicableLabelsForPlace);
    }

    private function handleUiTPASPricesUpdated(PricesUpdated $pricesUpdated): void
    {
        $this->logger->info('Update UiTPAS prices for event ' . $pricesUpdated->getEventId() . ' (if applied)');

        $this->commandBus->dispatch(
            new UpdateUiTPASPrices($pricesUpdated->getEventId(), $pricesUpdated->getTariffs())
        );
    }

    /**
     * @param CardSystem[] $cardSystems
     * @param Label[] $uitPasLabels
     * @return Label[]
     */
    private function determineApplicableLabelsForCardSystems(
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
    private function determineInapplicableLabels(
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
     * @param Label[] $labels
     */
    private function removeLabelsFromOffer(OfferType $offerType, string $offerId, array $labels): void
    {
        $this->logger->info(
            'Removing UiTPAS labels for irrelevant card systems from ' . strtolower($offerType->toString()) . ' ' . $offerId . ' (if applied)'
        );

        $commands = array_map(
            function (Label $label) use ($offerId) {
                return new RemoveLabelFromOffer(
                    $offerId,
                    $label->getName()->toString()
                );
            },
            $labels
        );

        $this->dispatchCommands($commands);
    }

    /**
     * @param Label[] $labels
     */
    private function addLabelsToOffer(OfferType $offerType, string $offerId, array $labels): void
    {
        if (count($labels) === 0) {
            return;
        }

        $this->logger->info(
            'Adding UiTPAS labels for active card systems on ' . strtolower($offerType->toString()) . ' ' . $offerId . '(if not applied yet)'
        );

        $commands = array_map(
            function (Label $label) use ($offerId) {
                return new AddLabelToOffer(
                    $offerId,
                    $label
                );
            },
            $labels
        );

        $this->dispatchCommands($commands);
    }

    /**
     * @param Label[] $labels
     */
    private function removeLabelsFromOrganizer(string $organizerId, array $labels): void
    {
        $this->logger->info(
            'Removing UiTPAS labels for irrelevant card systems from organizer ' . $organizerId . ' (if applied)'
        );

        $commands = array_map(
            function (Label $label) use ($organizerId) {
                return new RemoveLabelFromOrganizer(
                    $organizerId,
                    $label->getName()->toString()
                );
            },
            $labels
        );

        $this->dispatchCommands($commands);
    }

    /**
     * @param Label[] $labels
     */
    private function addLabelsToOrganizer(string $organizerId, array $labels): void
    {
        if (count($labels) === 0) {
            return;
        }

        $this->logger->info(
            'Adding UiTPAS labels for active card systems on organizer ' . $organizerId . '(if not applied yet)'
        );

        $commands = array_map(
            function (Label $label) use ($organizerId) {
                return new AddLabelToOrganizer(
                    $organizerId,
                    $label
                );
            },
            $labels
        );

        $this->dispatchCommands($commands);
    }

    /**
     * @param object[] $commands
     */
    private function dispatchCommands(array $commands): void
    {
        foreach ($commands as $command) {
            if ($command instanceof AddLabelToOffer || $command instanceof AddLabelToOrganizer) {
                $labelName = $command->getLabel()->getName()->toString();
            } elseif ($command instanceof RemoveLabelFromOffer || $command instanceof RemoveLabelFromOrganizer) {
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
