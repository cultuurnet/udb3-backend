<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\EventSourcing\DBAL\UniqueConstraintServiceInterface;
use ValueObjects\StringLiteral\StringLiteral;

class WebsiteUniqueConstraintService implements UniqueConstraintServiceInterface
{
    public function hasUniqueConstraint(DomainMessage $domainMessage): bool
    {
        return $domainMessage->getPayload() instanceof OrganizerCreatedWithUniqueWebsite ||
            $domainMessage->getPayload() instanceof WebsiteUpdated;
    }

    public function needsUpdateUniqueConstraint(DomainMessage $domainMessage): bool
    {
        return $domainMessage->getPayload() instanceof WebsiteUpdated;
    }

    public function getUniqueConstraintValue(DomainMessage $domainMessage): StringLiteral
    {
        if (!$this->hasUniqueConstraint($domainMessage)) {
            throw new \InvalidArgumentException('Given domain message has no unique constraint.');
        }

        /* @var OrganizerCreatedWithUniqueWebsite|WebsiteUpdated $payload */
        $payload = $domainMessage->getPayload();

        $websiteWithNoProtocol = preg_replace('/^https?:\/\/(www.)?/i', '', $payload->getWebsite());
        $websiteWithNoProtocolOrSlash = preg_replace('/\/$/', '', $websiteWithNoProtocol);

        return new StringLiteral($websiteWithNoProtocolOrSlash);
    }
}
