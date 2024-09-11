<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Notification\Swift;

use CultuurNet\UDB3\EventExport\EventExportResult;
use CultuurNet\UDB3\EventExport\Notification\BodyFactoryInterface;
use CultuurNet\UDB3\EventExport\Notification\SubjectFactoryInterface;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;

class DefaultMessageFactory implements MessageFactoryInterface
{
    private BodyFactoryInterface $plainTextBodyFactory;

    private BodyFactoryInterface $htmlBodyFactory;

    private SubjectFactoryInterface $subjectFactory;

    private string $senderAddress;

    private string $senderName;

    /**
     * @param string                  $senderAddress
     * @param string                  $senderName
     */
    public function __construct(
        BodyFactoryInterface $plainTextBodyFactory,
        BodyFactoryInterface $htmlBodyFactory,
        SubjectFactoryInterface $subjectFactory,
        $senderAddress,
        $senderName
    ) {
        $this->plainTextBodyFactory = $plainTextBodyFactory;
        $this->htmlBodyFactory = $htmlBodyFactory;
        $this->senderAddress = $senderAddress;
        $this->senderName = $senderName;
        $this->subjectFactory = $subjectFactory;
    }

    public function createMessageFor(EmailAddress $address, EventExportResult $eventExportResult): \Swift_Message
    {
        $message = new \Swift_Message($this->subjectFactory->getSubjectFor($eventExportResult));
        $message->setBody(
            $this->htmlBodyFactory->getBodyFor(
                $eventExportResult
            ),
            'text/html'
        );
        $message->addPart(
            $this->plainTextBodyFactory->getBodyFor(
                $eventExportResult
            ),
            'text/plain'
        );

        $message->addTo($address->toString());

        $message->setSender($this->senderAddress, $this->senderName);
        $message->setFrom($this->senderAddress, $this->senderName);

        return $message;
    }
}
