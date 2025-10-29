<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Ownership\RecipientStrategy;

use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\User\Recipients;
use CultuurNet\UDB3\User\UserIdentityDetails;

final class CombinedRecipientStrategy implements RecipientStrategy
{
    /** @var RecipientStrategy[] */
    private array $recipientStrategies;

    private ?UserIdentityDetails $fallbackUserIdentityDetails;

    public function __construct(RecipientStrategy ...$recipientStrategies)
    {
        $this->recipientStrategies = $recipientStrategies;
    }

    public function getRecipients(OwnershipItem $item): Recipients
    {
        $recipients = new Recipients();
        foreach ($this->recipientStrategies as $recipientStrategy) {
            foreach ($recipientStrategy->getRecipients($item) as $recipient) {
                $recipients->add($recipient);
            }
        }

        if ($this->fallbackUserIdentityDetails !== null && count($recipients->getRecipients()) === 0) {
            $recipients->add($this->fallbackUserIdentityDetails);
        }

        return $recipients;
    }

    public function withFallback(UserIdentityDetails $fallbackUserIdentityDetails): self
    {
        $c = clone $this;
        $c->fallbackUserIdentityDetails = $fallbackUserIdentityDetails;
        return $c;
    }
}
