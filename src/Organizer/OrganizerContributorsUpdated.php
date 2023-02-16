<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use CultuurNet\UDB3\Contributor\ContributorsUpdated;

final class OrganizerContributorsUpdated extends ContributorsUpdated
{
    public function __construct(string $id, string $iri)
    {
        parent::__construct($id, $iri);
    }

    public static function deserialize(array $data): OrganizerContributorsUpdated
    {
        return new self($data['id'], $data['iri']);
    }
}
