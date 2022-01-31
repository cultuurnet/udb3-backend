<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use ValueObjects\Web\PortNumber;
use ValueObjects\Web\Url as LegacyUrl;

final class WebsiteNormalizer
{
    public function normalizeUrl(Url $url): string
    {
        $legacyUrl = LegacyUrl::fromNative($url->toString());
        $domain = $legacyUrl->getDomain()->toNative();
        if (strpos($domain, 'www.') === 0) {
            $domain = substr($domain, strlen('www.'));
        }

        $port = $legacyUrl->getPort() instanceof PortNumber ? ':' . $legacyUrl->getPort()->toNative() : '';

        $queryString = $legacyUrl->getQueryString()->toNative();
        $fragment = $legacyUrl->getFragmentIdentifier()->toNative();

        $path = rtrim($legacyUrl->getPath()->toNative(), '/');
        if ($path === '' && ($queryString !== '' || $fragment !== '')) {
            $path = '/';
        }

        return $domain . $port . $path . $queryString . $fragment;
    }
}
