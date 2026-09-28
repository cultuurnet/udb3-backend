<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

use CultuurNet\UDB3\Support\Poll;

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
        $subject = $subject;
        $mailObjects = $this->getMailClient()->searchMails(
            'from:' . $from .
            ' to:' . $to .
            ' subject:' . $subject
        );
        assertCount(1, $mailObjects);
        $mailobject = $mailObjects[0];
        assertEquals($from, $mailobject->getFrom()->toString());
        assertEquals($to, $mailobject->getTo()->getByIndex(0)->toString());
        assertEquals($subject, $mailobject->getSubject());
        assertStringMatchesFormat(
            $this->fixtures->loadMail($messageType, $this->variableState),
            $mailobject->getContent()
        );
    }

    /**
     * @When I wait till there are :count mails in the mailbox
     */
    public function iWaitTillThereAreMailsInTheMailbox(int $count): void
    {
        Poll::until(fn (): bool => $this->getMailClient()->getMailCount() == $count, 5, $count . ' mail(s) in the mailbox');
    }
}
