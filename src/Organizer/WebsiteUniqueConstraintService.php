<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\EventSourcing\DBAL\UniqueConstraintService;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\Events\WebsiteUpdated;
use InvalidArgumentException;

class WebsiteUniqueConstraintService implements UniqueConstraintService
{
    private WebsiteNormalizer $websiteNormalizer;

    public function __construct(WebsiteNormalizer $websiteNormalizer)
    {
        $this->websiteNormalizer = $websiteNormalizer;
    }

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

        return $this->websiteNormalizer->normalizeUrl(new Url($payload->getWebsite()));
    }
}
