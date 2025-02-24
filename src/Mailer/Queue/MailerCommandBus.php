<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Queue;

use CultuurNet\UDB3\CommandHandling\ResqueCommandBus;

final class MailerCommandBus extends ResqueCommandBus
{
    public static function getQueueName(): string
    {
        return 'mails';
    }
}
