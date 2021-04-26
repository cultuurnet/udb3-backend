<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\EventSourcing\DBAL\UniqueConstraintService;
use InvalidArgumentException;

class WebsiteUniqueConstraintService implements UniqueConstraintService
{
    public function hasUniqueConstraint(DomainMessage $domainMessage): bool
    {
        return $domainMessage->getPayload() instanceof OrganizerCreatedWithUniqueWebsite ||
            $domainMessage->getPayload() instanceof WebsiteUpdated;
    }

    public function needsPreflightLookup(): bool
    {
        return true;
    }

    public function needsUpdateUniqueConstraint(DomainMessage $domainMessage): bool
    {
        return $domainMessage->getPayload() instanceof WebsiteUpdated;
    }

    public function getUniqueConstraintValue(DomainMessage $domainMessage): string
    {
        if (!$this->hasUniqueConstraint($domainMessage)) {
            throw new InvalidArgumentException('Given domain message has no unique constraint.');
        }

        /* @var OrganizerCreatedWithUniqueWebsite|WebsiteUpdated $payload */
        $payload = $domainMessage->getPayload();

        $websiteWithNoProtocol = preg_replace('/^https?:\/\/(www.)?/i', '', $payload->getWebsite());
        return preg_replace('/\/$/', '', $websiteWithNoProtocol);
    }
}
