<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

use function PHPUnit\Framework\assertEquals;

trait MailSteps
{
    /**
     * @When an :messageType mail has been sent from :from to :to with subject :subject
     */
    public function aMailHasBeenSentFromToWith(string $messageType, string $from, string $to, string $subject): void
    {
        $mailobject = $this->getMailClient()->getLatestEmail();
        assertEquals($from, $mailobject->getFrom()->toString());
        assertEquals($to, $mailobject->getTo()->getByIndex(0) ->toString());
        assertEquals($subject, $mailobject->getSubject());
        assertEquals(
            $this->fixtures->loadMail($messageType),
            $this->removeUuidFilePattern($mailobject->getContent())
        );
    }

    private function removeUuidFilePattern(string $value): string
    {
        $uuidFilePattern = '/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.(pdf|xlsx|json)/i';
        return preg_replace($uuidFilePattern, '', $value);
    }
}
