<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Support;

interface MailClient
{
    public function getLatestEmail(): EmailMessage;

    /**
     * @return EmailMessage[]
     */
    public function searchMails(string $query): array;

    public function getMailCount(): int;

    public function deleteAllMails(): void;
}
