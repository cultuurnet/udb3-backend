<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Contributor;

use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;

interface ContributorRepository
{
    public function getContributors(UUID $id): EmailAddresses;

    public function isContributor(UUID $id, EmailAddress $emailAddress): bool;

    public function updateContributors(UUID $id, EmailAddresses $emailAddresses, ItemType $itemType): void;
}
