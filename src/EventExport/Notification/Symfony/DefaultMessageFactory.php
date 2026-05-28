<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Notification\Symfony;

use CultuurNet\UDB3\EventExport\EventExportResult;
use CultuurNet\UDB3\EventExport\Notification\BodyFactoryInterface;
use CultuurNet\UDB3\EventExport\Notification\SubjectFactoryInterface;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class DefaultMessageFactory implements MessageFactoryInterface
{
    private BodyFactoryInterface $plainTextBodyFactory;

    private BodyFactoryInterface $htmlBodyFactory;

    private SubjectFactoryInterface $subjectFactory;

    private string $senderAddress;

    private string $senderName;

    public function __construct(
        BodyFactoryInterface $plainTextBodyFactory,
        BodyFactoryInterface $htmlBodyFactory,
        SubjectFactoryInterface $subjectFactory,
        string $senderAddress,
        string $senderName
    ) {
        $this->plainTextBodyFactory = $plainTextBodyFactory;
        $this->htmlBodyFactory = $htmlBodyFactory;
        $this->senderAddress = $senderAddress;
        $this->senderName = $senderName;
        $this->subjectFactory = $subjectFactory;
    }

    public function createMessageFor(EmailAddress $address, EventExportResult $eventExportResult): Email
    {
        $sender = new Address($this->senderAddress, $this->senderName);

        return (new Email())
            ->from($sender)
            ->sender($sender)
            ->to($address->toString())
            ->subject($this->subjectFactory->getSubjectFor($eventExportResult))
            ->text($this->plainTextBodyFactory->getBodyFor($eventExportResult))
            ->html($this->htmlBodyFactory->getBodyFor($eventExportResult));
    }
}
