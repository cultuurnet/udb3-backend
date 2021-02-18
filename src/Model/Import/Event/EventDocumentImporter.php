<?php

namespace CultuurNet\UDB3\Model\Import\Event;

use Broadway\CommandHandling\CommandBus;
use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\Repository;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerInterface;
use CultuurNet\UDB3\ApiGuard\Consumer\Specification\ConsumerSpecificationInterface;
use CultuurNet\UDB3\Event\Commands\ImportLabels;
use CultuurNet\UDB3\Event\Commands\DeleteCurrentOrganizer;
use CultuurNet\UDB3\Event\Commands\DeleteTypicalAgeRange;
use CultuurNet\UDB3\Event\Commands\ImportImages;
use CultuurNet\UDB3\Event\Commands\UpdateAudience;
use CultuurNet\UDB3\Event\Commands\UpdateBookingInfo;
use CultuurNet\UDB3\Event\Commands\UpdateCalendar;
use CultuurNet\UDB3\Event\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Event\Commands\UpdateDescription;
use CultuurNet\UDB3\Event\Commands\UpdateLocation;
use CultuurNet\UDB3\Event\Commands\UpdateOrganizer;
use CultuurNet\UDB3\Event\Commands\UpdatePriceInfo;
use CultuurNet\UDB3\Event\Commands\UpdateTheme;
use CultuurNet\UDB3\Event\Commands\UpdateTitle;
use CultuurNet\UDB3\Event\Commands\UpdateType;
use CultuurNet\UDB3\Event\Commands\UpdateTypicalAgeRange;
use CultuurNet\UDB3\Event\Event as EventAggregate;
use CultuurNet\UDB3\Event\ValueObjects\Audience;
use CultuurNet\UDB3\Event\ValueObjects\AudienceType;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\Event\Event;
use CultuurNet\UDB3\Model\Import\DecodedDocument;
use CultuurNet\UDB3\Model\Import\DocumentImporterInterface;
use CultuurNet\UDB3\Model\Import\MediaObject\ImageCollectionFactory;
use CultuurNet\UDB3\Model\Import\Taxonomy\Label\LockedLabelRepository;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class EventDocumentImporter implements DocumentImporterInterface
{
    /**
     * @var Repository
     */
    private $aggregateRepository;

    /**
     * @var DenormalizerInterface
     */
    private $eventDenormalizer;

    /**
     * @var ImageCollectionFactory
     */
    private $imageCollectionFactory;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var ConsumerSpecificationInterface
     */
    private $shouldApprove;

    /**
     * @var LockedLabelRepository
     */
    private $lockedLabelRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Repository $aggregateRepository,
        DenormalizerInterface $eventDenormalizer,
        ImageCollectionFactory $imageCollectionFactory,
        CommandBus $commandBus,
        ConsumerSpecificationInterface $shouldApprove,
        LockedLabelRepository $lockedLabelRepository,
        LoggerInterface $logger
    ) {
        $this->aggregateRepository = $aggregateRepository;
        $this->eventDenormalizer = $eventDenormalizer;
        $this->imageCollectionFactory = $imageCollectionFactory;
        $this->commandBus = $commandBus;
        $this->shouldApprove = $shouldApprove;
        $this->lockedLabelRepository = $lockedLabelRepository;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function import(DecodedDocument $decodedDocument, ConsumerInterface $consumer = null)
    {
        $id = $decodedDocument->getId();

        $this->logger->log(LogLevel::DEBUG, $decodedDocument->toJson(), ['event_id' => $id]);

        try {
            $exists = !is_null($this->aggregateRepository->load($id));
        } catch (AggregateNotFoundException $e) {
            $exists = false;
        }

        /* @var Event $import */
        $importData = $decodedDocument->getBody();
        $import = $this->eventDenormalizer->denormalize($importData, Event::class);

        $adapter = new Udb3ModelToLegacyEventAdapter($import);

        $mainLanguage = $adapter->getMainLanguage();
        $title = $adapter->getTitle();
        $type = $adapter->getType();
        $theme = $adapter->getTheme();
        $location = $adapter->getLocation();
        $calendar = $adapter->getCalendar();
        $publishDate = $adapter->getAvailableFrom(new \DateTimeImmutable());

        $commands = [];
        if (!$exists) {
            $event = EventAggregate::create(
                $id,
                $mainLanguage,
                $title,
                $type,
                $location,
                $calendar,
                $theme
            );

            // New events created via the import API should always be set to
            // ready_for_validation.
            // The publish date in EventCreated does not seem to trigger a
            // wfStatus "ready_for_validation" on the json-ld so we manually
            // publish the event after creating it.
            // Existing events should always keep their original status, so
            // only do this publish command for new events.
            $event->publish($publishDate);

            // Events created by specific API partners should automatically be
            // approved.
            if ($consumer && $this->shouldApprove->satisfiedBy($consumer)) {
                $event->approve();
            }

            $this->aggregateRepository->save($event);
        } else {
            $commands[] = new UpdateTitle(
                $id,
                $mainLanguage,
                $title
            );

            $commands[] = new UpdateType($id, $type);
            $commands[] = new UpdateLocation($id, $location);
            $commands[] = new UpdateCalendar($id, $calendar);

            if ($theme) {
                $commands[] = new UpdateTheme($id, $theme);
            }
        }

        if ($location->isDummyPlaceForEducation()) {
            $audienceType = AudienceType::EDUCATION();
        } else {
            $audienceType = $adapter->getAudienceType();
        }
        $commands[] = new UpdateAudience($id, new Audience($audienceType));

        $bookingInfo = $adapter->getBookingInfo();
        $commands[] = new UpdateBookingInfo($id, $bookingInfo);

        $contactPoint = $adapter->getContactPoint();
        $commands[] = new UpdateContactPoint($id, $contactPoint);

        $description = $adapter->getDescription();
        if ($description) {
            $commands[] = new UpdateDescription($id, $mainLanguage, $description);
        }

        $ageRange = $adapter->getAgeRange();
        if ($ageRange) {
            $commands[] = new UpdateTypicalAgeRange($id, $ageRange);
        } else {
            $commands[] = new DeleteTypicalAgeRange($id);
        }

        $priceInfo = $adapter->getPriceInfo();
        if ($priceInfo) {
            $commands[] = new UpdatePriceInfo($id, $priceInfo);
        }

        foreach ($adapter->getTitleTranslations() as $language => $title) {
            $language = new Language($language);
            $commands[] = new UpdateTitle($id, $language, $title);
        }

        foreach ($adapter->getDescriptionTranslations() as $language => $description) {
            $language = new Language($language);
            $commands[] = new UpdateDescription($id, $language, $description);
        }

        $images = $this->imageCollectionFactory->fromMediaObjectReferences($import->getMediaObjectReferences());
        $commands[] = new ImportImages($id, $images);

        $lockedLabels = $this->lockedLabelRepository->getLockedLabelsForItem($id);
        $unlockedLabels = $this->lockedLabelRepository->getUnlockedLabelsForItem($id);
        $commands[] = (new ImportLabels($id, $import->getLabels()))
            ->withLabelsToKeepIfAlreadyOnOffer($lockedLabels)
            ->withLabelsToRemoveWhenOnOffer($unlockedLabels);

        // Update the organizer only at the end, because it can trigger UiTPAS to send messages to another worker
        // which might cause race conditions if we're still dispatching other commands here as well.
        $organizerId = $adapter->getOrganizerId();
        if ($organizerId) {
            $commands[] = new UpdateOrganizer($id, $organizerId);
        } else {
            $commands[] = new DeleteCurrentOrganizer($id);
        }

        $this->dispatchCommands($commands, $id);
    }

    private function dispatchCommands(array $commands, string $entityId)
    {
        $logContext = [
            'entity_id' => $entityId,
        ];

        $this->logger->log(
            LogLevel::DEBUG,
            'commands to dispatch for import of entity {entity_id}: {commands}',
            $logContext + [
                'commands' => implode(', ', array_map('get_class', $commands)),
            ]
        );

        foreach ($commands as $command) {
            $commandId = $this->commandBus->dispatch($command);
            if (empty($commandId)) {
                $commandId = '(((empty)))';
            }

            $this->logger->log(
                LogLevel::DEBUG,
                'dispatched command: {class} with id {command_id}, targeting event {entity_id}',
                $logContext + [
                    'class' => get_class($command),
                    'command_id' => $commandId,
                ]
            );
        }
    }
}
