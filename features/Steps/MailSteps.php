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
        sleep(1);
        $mailobject = $this->getMailClient()->getLatestEmail();
        assertEquals($from, $mailobject->getFrom()->toString());
        assertEquals($to, $mailobject->getTo()->getByIndex(0)->toString());
        assertStringMatchesFormat('%A' . $subject . '%A', $mailobject->getSubject());
        assertStringMatchesFormat(
            $this->fixtures->loadMail($messageType),
            $mailobject->getContent()
        );
    }
}
