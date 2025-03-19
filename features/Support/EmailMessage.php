<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Support;

use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;

final class EmailMessage
{
    private string $id;

    private EmailAddress $from;

    private EmailAddresses $to;

    private string $subject;

    private string $content;

    private array $attachments;

    public function __construct(
        string $id,
        EmailAddress $from,
        EmailAddresses $to,
        string $subject,
        string $content,
        array $attachments
    ) {
        $this->id = $id;
        $this->from = $from;
        $this->to = $to;
        $this->subject = $subject;
        $this->content = $content;
        $this->attachments = $attachments;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFrom(): EmailAddress
    {
        return $this->from;
    }

    public function getTo(): EmailAddresses
    {
        return $this->to;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getAttachments(): array
    {
        return $this->attachments;
    }

    private static function getAddressesTo(array $data): EmailAddresses
    {
        return EmailAddresses::fromArray(array_map(fn ($contact) => new EmailAddress($contact['Address']), $data));
    }

    public static function createFromMailPitData(array $data): self
    {
        return new self(
            $data['ID'],
            new EmailAddress($data['From']['Address']),
            self::getAddressesTo($data['To']),
            $data['Subject'],
            $data['HTML'],
            $data['Attachments']
        );
    }
}
