<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Support;

interface MailClient
{
    public function getEmailById(string $messageId): EmailMessage;
    public function getLatestEmail(): EmailMessage;
}
