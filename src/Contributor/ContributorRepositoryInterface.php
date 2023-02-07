<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Contributor;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;

interface ContributorRepositoryInterface
{
    public function getContributors(UUID $id): EmailAddresses;

    public function isContributor(UUID $id, EmailAddress $emailAddress): bool;

    public function addContributor(UUID $id, EmailAddress $emailAddress): void;

    public function deleteContributors(UUID $id): void;
}
