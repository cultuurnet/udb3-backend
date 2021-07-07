<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\EventSourcing\DBAL\UniqueConstraintService;
use InvalidArgumentException;
use League\Uri\Components\Port;
use ValueObjects\Web\NullPortNumber;
use ValueObjects\Web\PortNumber;
use ValueObjects\Web\Url;

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

        return $this->normalizeUrl($payload->getWebsite());
    }

    private function normalizeUrl(Url $url): string
    {
        $domain = $url->getDomain()->toNative();
        if (strpos($domain, 'www.') === 0) {
            $domain = substr($domain, strlen('www.'));
        }

        $port = $url->getPort() instanceof PortNumber ? ':' . $url->getPort()->toNative() : '';

        $queryString = $url->getQueryString()->toNative();
        $fragment = $url->getFragmentIdentifier()->toNative();

        $path = rtrim($url->getPath()->toNative(), '/');
        if ($path === '' && ($queryString !== '' || $fragment !== '')) {
            $path = '/';
        }

        return implode(
            '',
            [
                $domain,
                $port,
                $path,
                $queryString,
                $fragment,
            ]
        );
    }
}
