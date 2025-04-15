<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Support;

interface MailClient
{
    public function getLatestEmail(): EmailMessage;

    public function getMailCount(): int;
}
