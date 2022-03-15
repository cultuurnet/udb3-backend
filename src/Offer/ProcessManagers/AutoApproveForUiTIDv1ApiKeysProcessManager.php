<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ProcessManagers;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerReadRepository;
use CultuurNet\UDB3\ApiGuard\Consumer\Specification\ConsumerSpecification;
use CultuurNet\UDB3\Offer\Events\Moderation\AbstractPublished;
use CultuurNet\UDB3\Offer\OfferRepository;

final class AutoApproveForUiTIDv1ApiKeysProcessManager implements EventListener
{
    private OfferRepository $offerRepository;
    private ConsumerReadRepository $apiConsumerReadRepository;
    private ConsumerSpecification $shouldAutoApprove;

    public function __construct(
        OfferRepository $offerRepository,
        ConsumerReadRepository $apiConsumerReadRepository,
        ConsumerSpecification $shouldAutoApprove
    ) {
        $this->offerRepository = $offerRepository;
        $this->apiConsumerReadRepository = $apiConsumerReadRepository;
        $this->shouldAutoApprove = $shouldAutoApprove;
    }

    /**
     * Listens to Published events on Offer classes, and check if the API key that was used to publish the Offer is
     * allowed to have their offers auto-approved and if so approves it.
     * Ideally this would dispatch an Approve command instead of working on the aggregate directly, but that's not
     * possible because the user/API client does not have the AANBOD_BEHEREN permission in this context.
     */
    public function handle(DomainMessage $domainMessage): void
    {
        $event = $domainMessage->getPayload();
        if (!($event instanceof AbstractPublished)) {
            return;
        }

        $metadata = $domainMessage->getMetadata()->serialize();
        if (!isset($metadata['auth_api_key'])) {
            return;
        }

        $consumer = $this->apiConsumerReadRepository->getConsumer(new ApiKey($metadata['auth_api_key']));
        if ($consumer === null) {
            return;
        }

        $shouldAutoApprove = $this->shouldAutoApprove->satisfiedBy($consumer);
        if ($shouldAutoApprove) {
            $offer = $this->offerRepository->load($event->getItemId());
            $offer->approve();
            $this->offerRepository->save($offer);
        }
    }
}
