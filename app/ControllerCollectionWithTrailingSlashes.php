<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex;

use Silex\ControllerCollection;

final class ControllerCollectionWithTrailingSlashes extends ControllerCollection
{
    /**
     * Overrides the ControllerCollection::match() method that gets called for every route registration, so it can add
     * a trailing slash if missing.
     * URLs on incoming requests will be rewritten to add a trailing slash as well if missing, so any route works with
     * or without trailing slash this way.
     */
    public function match($pattern, $to = null)
    {
        // Trim trailing slashes, if any.
        $pattern = rtrim($pattern, '/');

        // Add a single trailing slash.
        $pattern .= '/';

        return parent::match($pattern, $to);
    }
}
