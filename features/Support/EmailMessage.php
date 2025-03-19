<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Support;

use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;

final class EmailMessage
{
    private string $id;

    private EmailAddress $from;

    /**
     * @var EmailAddress[]
     */
    private array $to;

    private string $subject;

    private string $html;

    private array $attachments;

    public function __construct(array $data)
    {
        $this->id = $data['ID'];
        $this->from = new EmailAddress($data['From']['Address']);
        $this->to = $this->getAddressesTo($data['To']);
        $this->subject = $data['Subject'];
        $this->html = $data['HTML'];
        $this->attachments = $data['Attachments'];
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFrom(): EmailAddress
    {
        return $this->from;
    }

    /**
     * @return EmailAddress[]
     */
    public function getTo(): array
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

    public function getAttachments(): array
    {
        return $this->attachments;
    }

    private function getAddressesTo(array $data): array
    {
        return array_map(fn ($contact) => new EmailAddress($contact['Address']), $data);
    }
}
