<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Contributor;

use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;

interface ContributorRepository
{
    public function getContributors(Uuid $id): EmailAddresses;

    public function isContributor(Uuid $id, EmailAddress $emailAddress): bool;

    public function updateContributors(Uuid $id, EmailAddresses $emailAddresses, ItemType $itemType): void;
}
