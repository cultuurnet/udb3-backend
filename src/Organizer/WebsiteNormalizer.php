<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;

final class WebsiteNormalizer
{
    public function normalizeUrl(Url $url): string
    {
        $domain = $url->getDomain();
        if (strpos($domain, 'www.') === 0) {
            $domain = substr($domain, strlen('www.'));
        }

        $port = $url->getPort() ? ':' . $url->getPort()->toInteger() : '';

        $queryString = $url->getQueryString() ? '?' . $url->getQueryString() : '';
        $fragment = $url->getFragmentIdentifier() ? '#' . $url->getFragmentIdentifier() : '';

        $path = rtrim($url->getPath() ?: '', '/');
        if ($path === '' && ($queryString !== '' || $fragment !== '')) {
            $path = '/';
        }

        return $domain . $port . $path . $queryString . $fragment;
    }
}
