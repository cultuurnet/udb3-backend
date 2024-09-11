<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\Metadata;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;

class OfferMetadataProjector implements EventListener
{
    use DelegateEventHandlingToSpecificMethodTrait;

    private OfferMetadataRepository $offerMetadataRepository;

    /**
     * @var array<string,string>
     */
    private array $apiKeyConsumerMapping;

    public function __construct(
        OfferMetadataRepository $repository,
        array $apiKeyConsumerMapping
    ) {
        $this->offerMetadataRepository = $repository;
        $this->apiKeyConsumerMapping = $apiKeyConsumerMapping;
    }

    public function applyEventCreated(EventCreated $eventCreated, DomainMessage $domainMessage): void
    {
        $this->projectMetadataForOffer($eventCreated->getEventId(), $domainMessage->getMetadata());
    }

    public function applyPlaceCreated(PlaceCreated $placeCreated, DomainMessage $domainMessage): void
    {
        $this->projectMetadataForOffer($placeCreated->getPlaceId(), $domainMessage->getMetadata());
    }

    public function applyEventCopied(EventCopied $eventCopied, DomainMessage $domainMessage): void
    {
        $this->projectMetadataForOffer($eventCopied->getItemId(), $domainMessage->getMetadata());
    }

    public function applyEventImportedFromUDB2(EventImportedFromUDB2 $eventImportedFromUDB2, DomainMessage $domainMessage): void
    {
        $this->projectMetadataForOffer($eventImportedFromUDB2->getEventId(), $domainMessage->getMetadata());
    }

    public function applyPlaceImportedFromUDB2(PlaceImportedFromUDB2 $placeImportedFromUDB2, DomainMessage $domainMessage): void
    {
        $this->projectMetadataForOffer($placeImportedFromUDB2->getActorId(), $domainMessage->getMetadata());
    }

    private function projectMetadataForOffer(string $offerId, Metadata $metadata): void
    {
        try {
            $offerMetadata = $this->offerMetadataRepository->get($offerId);
        } catch (EntityNotFoundException $e) {
            $offerMetadata = OfferMetadata::default($offerId);
        }

        $createdByApiConsumer = $this->getCreatedByApiConsumerFromMetadata($metadata);
        $offerMetadata = $offerMetadata->withCreatedByApiConsumer($createdByApiConsumer);

        $this->offerMetadataRepository->save($offerMetadata);
    }

    private function getCreatedByApiConsumerFromMetadata(Metadata $metadata): string
    {
        $properties = $metadata->serialize();

        if (!isset($properties['auth_api_key']) && !isset($properties['auth_api_client_id'])) {
            return 'unknown';
        }

        $apiKey = $properties['auth_api_key'] ?? $properties['auth_api_client_id'];
        if (!array_key_exists($apiKey, $this->apiKeyConsumerMapping)) {
            return 'other';
        }

        return $this->apiKeyConsumerMapping[$apiKey];
    }
}
