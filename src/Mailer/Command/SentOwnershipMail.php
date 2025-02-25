<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Command;

use CultuurNet\UDB3\CommandHandling\AsyncCommand;
use CultuurNet\UDB3\CommandHandling\AsyncCommandTrait;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;

final class SentOwnershipMail implements AsyncCommand
{
    use AsyncCommandTrait;

    private Uuid $uuid;
    private EmailAddress $to;
    private string $subject;
    private string $html;
    private string $text;

    public function __construct(Uuid $uuid, EmailAddress $to, string $subject, string $html, string $text)
    {
        $this->uuid = $uuid;
        $this->to = $to;
        $this->subject = $subject;
        $this->html = $html;
        $this->text = $text;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function getTo(): EmailAddress
    {
        return $this->to;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getHtml(): string
    {
        return $this->html;
    }

    public function getText(): string
    {
        return $this->text;
    }
}
