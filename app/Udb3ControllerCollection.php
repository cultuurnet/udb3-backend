<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex;

use Silex\ControllerCollection;

final class Udb3ControllerCollection extends ControllerCollection
{
    /**
     * Overrides the ControllerCollection::match() method that gets called for every route registration, so it can add
     * a trailing slash if missing.
     * URLs on incoming requests will be rewritten to add a trailing slash as well if missing, so any route works with
     * or without trailing slash this way.
     */
    public function match($pattern, $to = null)
    {
        // Don't alter the pattern if its a catch-all route as registered for OPTIONS requests and for rewrites of URLs
        // for other methods.
        if ($pattern === '/{path}') {
            return parent::match($pattern, $to);
        }

        // Trim trailing slashes, if any.
        $pattern = rtrim($pattern, '/');

        // Add a single trailing slash.
        $pattern .= '/';

        $match = parent::match($pattern, $to);

        // Make it possible to use an offerType wildcard that only matches if it's either "events" or "places".
        if (strpos($pattern, '{offerType}') !== false) {
            $match->assert('offerType', '(events|places)');
        }

        return $match;
    }
}
