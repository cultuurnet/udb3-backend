<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailinglist\Client;

interface MailinglistClient
{
    public function subscribe(string $email, string $mailingListId): void;
}
