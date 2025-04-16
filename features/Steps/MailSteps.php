<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertStringMatchesFormat;

trait MailSteps
{
    /**
     * @When an :messageType mail has been sent from :from to :to with subject :subject
     */
    public function aMailHasBeenSentFromToWith(string $messageType, string $from, string $to, string $subject): void
    {
        $variableState = str_starts_with($messageType, 'ownership') ? $this->variableState : null;
        $mailObjects = $this->getMailClient()->searchMails('from:' . $from . ' to:' . $to . ' subject:' . str_replace('%A', ' ', $subject));
        assertCount(1, $mailObjects);
        $mailobject = $mailObjects[0];
        assertEquals($from, $mailobject->getFrom()->toString());
        assertEquals($to, $mailobject->getTo()->getByIndex(0)->toString());
        assertStringMatchesFormat('%A' . $subject . '%A', $mailobject->getSubject());
        assertStringMatchesFormat(
            $this->fixtures->loadMail($messageType, $variableState),
            $mailobject->getContent()
        );
    }

    /**
     * @When I wait till there are :count mails in the mailbox
     */
    public function iWaitTillThereAreMailsInTheMailbox(int $count): void
    {
        $elapsedTime = 0;
        do {
            $messagesCount = $this->getMailClient()->getMailCount();
            if ($messagesCount != $count) {
                sleep(1);
                $elapsedTime++;
            }
        } while ($this->responseState->getTotalItems() != 1 && $elapsedTime++ < 5);
    }
}
