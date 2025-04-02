<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;

interface MailsSentRepository
{
    public function addMailSent(Uuid $identifier, EmailAddress $email, string $type, \DateTimeInterface $dateTime): void;
}
