<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Ownership\RecipientStrategy;

use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\User\Recipients;

final class CombinedRecipientStrategy implements RecipientStrategy
{
    /** @var RecipientStrategy[] */
    private array $recipientStrategies;

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

        return $recipients;
    }
}
