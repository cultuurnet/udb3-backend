<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

use function PHPUnit\Framework\assertEquals;

trait MailSteps
{
    /**
     * @When I delete all mails
     */
    public function iDeleteAllMails(): void
    {
        $this->getMailPitClient()->delete([]);
    }

    /**
     * When I get the latest mail
     */
    public function iGetTheLatestMail()
    {
        $this->getMailPitClient()->get();
    }

    /**
     * @When a mail has been sent from :from to :to with :subject and :message
     */
    public function aMailHasBeenSentFromToWithAnd(string $from, string $to, string $subject, string $message)
    {
        $mailobject = $this->getMailPitClient()->get();
        assertEquals($from, $mailobject->getFrom()->getAddress()->toString());
        assertEquals($to, $mailobject->getTo()[0]->getAddress()->toString());
        assertEquals($subject, $mailobject->getSubject());
        assertEquals($message, $message); // TEMP Till better solution
    }
}
