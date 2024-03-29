<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Notification\Swift;

use CultuurNet\UDB3\EventExport\EventExportResult;
use CultuurNet\UDB3\EventExport\Notification\BodyFactoryInterface;
use CultuurNet\UDB3\EventExport\Notification\SubjectFactoryInterface;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;

class DefaultMessageFactory implements MessageFactoryInterface
{
    /**
     * @var BodyFactoryInterface
     */
    private $plainTextBodyFactory;

    /**
     * @var BodyFactoryInterface
     */
    private $htmlBodyFactory;

    /**
     * @var SubjectFactoryInterface
     */
    private $subjectFactory;

    /**
     * @var string
     */
    private $senderAddress;

    /**
     * @var string
     */
    private $senderName;

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

    /**
     * @return \Swift_Message
     */
    public function createMessageFor(EmailAddress $address, EventExportResult $eventExportResult)
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
