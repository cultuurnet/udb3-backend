<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Ownership\RecipientStrategy;

use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\User\UserIdentityDetails;

interface RecipientStrategy
{
    /**
     * @throws DocumentDoesNotExist
     * @return UserIdentityDetails[]
     */
    public function getRecipients(OwnershipItem $item): array;
}
