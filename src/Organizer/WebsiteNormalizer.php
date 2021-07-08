<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use ValueObjects\Web\PortNumber;
use ValueObjects\Web\Url;

final class WebsiteNormalizer
{
    public function normalizeUrl(Url $url): string
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

        return $domain . $port . $path . $queryString . $fragment;
    }
}
