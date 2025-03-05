<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Ownership\RecipientStrategy;

use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\User\UserIdentityDetails;

final class CombinedRecipientStrategy implements RecipientStrategy
{
    /** @var RecipientStrategy[] */
    private array $recipientStrategies;

    public function __construct(RecipientStrategy ...$recipientStrategies)
    {
        $this->recipientStrategies = $recipientStrategies;
    }

    /** @return UserIdentityDetails[] */
    public function getRecipients(OwnershipItem $item): array
    {
        $output = [];
        foreach ($this->recipientStrategies as $voter) {
            try {
                foreach($voter->getRecipients($item) as $recipient) {
                    $output[$recipient->getEmailAddress()] = $recipient;
                }
            } catch (DocumentDoesNotExist $e) {
                // Do nothing
            }
        }

        return $output;
    }
}
