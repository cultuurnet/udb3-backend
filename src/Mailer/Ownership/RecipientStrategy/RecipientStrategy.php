<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Ownership\RecipientStrategy;

use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\User\UserIdentityDetails;

interface RecipientStrategy
{
    /**
     * @return UserIdentityDetails[]
     */
    public function getRecipients(OwnershipItem $item): array;
}
