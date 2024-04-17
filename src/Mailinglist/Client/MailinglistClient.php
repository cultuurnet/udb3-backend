<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailinglist\Client;

use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;

interface MailinglistClient
{
    public function subscribe(EmailAddress $email, string $mailingListId): void;
}
