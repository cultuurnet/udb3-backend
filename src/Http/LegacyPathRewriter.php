<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use Psr\Http\Message\ServerRequestInterface;

final class LegacyPathRewriter
{
    private const REWRITES = [
        // Pluralize /event and /place
        '/^(\/)?(event|place)($|\/.*)/' => '${1}${2}s${3}',

        // Convert known legacy camelCase resource/collection names to kebab-case
        '/bookingAvailability/' => 'booking-availability',
        '/bookingInfo/' => 'booking-info',
        '/cardSystems/' => 'card-systems',
        '/contactPoint/' => 'contact-point',
        '/distributionKey/' => 'distribution-key',
        '/majorInfo/' => 'major-info',
        '/priceInfo/' => 'price-info',
        '/subEvents/' => 'sub-events',
        '/typicalAgeRange/' => 'typical-age-range',

        // Convert old "calsum" path to "calendar-summary"
        '/\/calsum/' => '/calendar-summary',

        // Convert old "news_articles" path to "news-articles"
        '/news_articles/' => 'news-articles',

        // Add trailing slash if missing
        '/^(.*)(?<!\/)$/' => '${1}/',
    ];

    public function rewritePath(string $path): string
    {
        return preg_replace(array_keys(self::REWRITES), array_values(self::REWRITES), $path);
    }

    public function rewriteRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        $uri = $request->getUri();
        $path = $uri->getPath();
        $rewrittenPath = $this->rewritePath($path);
        $rewrittenUri = $uri->withPath($rewrittenPath);
        return $request->withUri($rewrittenUri);
    }
}
