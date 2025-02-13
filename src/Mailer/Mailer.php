<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer;

use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;

interface Mailer
{
    public function send(EmailAddress $to, string $subject, string $htmlTemplate, string $textTemplate, array $variables = []): bool;
}
