<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Ownership\RecipientStrategy;

use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\User\Recipients;
use CultuurNet\UDB3\User\UserIdentityDetails;

final class FallbackRecipientStrategy implements RecipientStrategy
{
    /**
     * @var RecipientStrategy[]
     */
    private array $recipientStrategies;

    private UserIdentityDetails $fallbackUserIdentityDetails;

    public function __construct(UserIdentityDetails $fallbackUserIdentityDetails, RecipientStrategy ...$recipientStrategies)
    {
        $this->fallbackUserIdentityDetails = $fallbackUserIdentityDetails;
        $this->recipientStrategies = $recipientStrategies;
    }

    public function getRecipients(OwnershipItem $item): Recipients
    {
        $recipients = new Recipients();
        foreach ($this->recipientStrategies as $voter) {
            foreach ($voter->getRecipients($item) as $recipient) {
                $recipients->add($recipient);
            }
        }

        if (count($recipients->getRecipients()) === 0) {
            $recipients->add($this->fallbackUserIdentityDetails);
        }

        return $recipients;
    }
}
