<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Contributor\ContributorsUpdated;

final class EventContributorsUpdated extends ContributorsUpdated
{
    public static function deserialize(array $data): EventContributorsUpdated
    {
        return new self($data['id'], $data['iri']);
    }
}
