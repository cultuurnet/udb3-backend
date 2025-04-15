<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertStringMatchesFormat;

trait MailSteps
{
    /**
     * @When an :messageType mail has been sent from :from to :to with subject :subject
     */
    public function aMailHasBeenSentFromToWith(string $messageType, string $from, string $to, string $subject): void
    {
        $mailobject = $this->getMailClient()->getLatestEmail();
        assertEquals($from, $mailobject->getFrom()->toString());
        assertEquals($to, $mailobject->getTo()->getByIndex(0)->toString());
        assertStringMatchesFormat('%A' . $subject . '%A', $mailobject->getSubject());
        assertStringMatchesFormat(
            $this->fixtures->loadMail($messageType),
            $mailobject->getContent()
        );
    }

    /**
     * @When I wait till there are :count mails in the mailbox
     */
    private function iWaitTillThereAreMailsInTheMailbox(int $count): void
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
