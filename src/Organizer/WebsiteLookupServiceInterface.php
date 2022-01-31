<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;

interface WebsiteLookupServiceInterface
{
    /**
     * @return string|null
     *   UUID of the existing organizer or null if the url has not been used yet.
     */
    public function lookup(Url $url);
}
