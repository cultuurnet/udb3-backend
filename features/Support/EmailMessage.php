<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Support;

final class EmailMessage
{
    private string $id;

    private ?EmailContact $from;

    /**
     * @var EmailContact[]
     */
    private array $to;

    private string $subject;

    private string $html;

    private array $attachments;

    public function __construct(array $data)
    {
        $this->id = $data['ID'];
        $this->from = EmailContact::deserialize($data['From']);
        $this->to = $this->getAddressesTo($data['To']);
        $this->subject = $data['Subject'];
        $this->html = $data['HTML'];
        $this->attachments = $data['Attachments'];
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFrom(): EmailContact
    {
        return $this->from;
    }

    /**
     * @return EmailContact[]
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
        $addressesTo = [];
        foreach ($data as $addressTo) {
            $addressesTo[] = EmailContact::deserialize($addressTo);
        }
        return $addressesTo;
    }
}
