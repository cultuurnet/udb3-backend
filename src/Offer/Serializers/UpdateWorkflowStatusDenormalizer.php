<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Serializers;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Event\Commands\Moderation\Approve as ApproveEvent;
use CultuurNet\UDB3\Event\Commands\Moderation\Publish as PublishEvent;
use CultuurNet\UDB3\Event\Commands\Moderation\Reject as RejectEvent;
use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;
use CultuurNet\UDB3\Offer\Commands\DeleteOffer;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Commands\Moderation\Approve as ApprovePlace;
use CultuurNet\UDB3\Place\Commands\Moderation\Publish as PublishPlace;
use CultuurNet\UDB3\Place\Commands\Moderation\Reject as RejectPlace;
use CultuurNet\UDB3\StringLiteral;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class UpdateWorkflowStatusDenormalizer implements DenormalizerInterface
{
    private OfferType $offerType;
    private string $offerId;

    public function __construct(OfferType $offerType, string $offerId)
    {
        $this->offerType = $offerType;
        $this->offerId = $offerId;
    }

    public function denormalize($data, $type, $format = null, array $context = []): ?AbstractCommand
    {
        $workflowStatus = new WorkflowStatus($data['workflowStatus']);

        if ($workflowStatus->sameAs(WorkflowStatus::READY_FOR_VALIDATION())) {
            $availableFrom = isset($data['availableFrom']) ? DateTimeFactory::fromISO8601($data['availableFrom']) : null;
            return $this->offerType->sameAs(OfferType::event())
                ? new PublishEvent($this->offerId, $availableFrom)
                : new PublishPlace($this->offerId, $availableFrom);
        }

        if ($workflowStatus->sameAs(WorkflowStatus::APPROVED())) {
            return $this->offerType->sameAs(OfferType::event())
                ? new ApproveEvent($this->offerId)
                : new ApprovePlace($this->offerId);
        }

        if ($workflowStatus->sameAs(WorkflowStatus::REJECTED())) {
            $reason = new StringLiteral($data['reason']);
            return $this->offerType->sameAs(OfferType::event())
                ? new RejectEvent($this->offerId, $reason)
                : new RejectPlace($this->offerId, $reason);
        }

        if ($workflowStatus->sameAs(WorkflowStatus::DELETED())) {
            return new DeleteOffer($this->offerId);
        }

        // If the workflowStatus property is set to DRAFT return nothing. Either the offer is already a draft and we
        // don't need to do anything, or the offer is not a draft anymore but you cannot move an offer back to the draft
        // status.
        return null;
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === AbstractCommand::class;
    }
}
