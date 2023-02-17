<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Contributor\ContributorsUpdated;

final class PlaceContributorsUpdated extends ContributorsUpdated
{
    public static function deserialize(array $data): PlaceContributorsUpdated
    {
        return new self($data['id'], $data['iri']);
    }
}
