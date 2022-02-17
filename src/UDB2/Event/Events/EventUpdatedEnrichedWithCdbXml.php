<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\Event\Events;

use CultuurNet\UDB3\Cdb\CdbXmlContainerInterface;
use CultuurNet\UDB3\HasCdbXmlTrait;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\UDB2\DomainEvents\EventUpdated;
use DateTimeImmutable;
use CultuurNet\UDB3\StringLiteral;

class EventUpdatedEnrichedWithCdbXml extends EventUpdated implements CdbXmlContainerInterface
{
    use HasCdbXmlTrait;

    public function __construct(
        StringLiteral $eventId,
        DateTimeImmutable $time,
        StringLiteral $author,
        Url $url,
        StringLiteral $cdbXml,
        StringLiteral $cdbXmlNamespaceUri
    ) {
        parent::__construct($eventId, $time, $author, $url);

        $this->setCdbXml((string) $cdbXml);
        $this->setCdbXmlNamespaceUri((string) $cdbXmlNamespaceUri);
    }

    public static function fromEventUpdated(
        EventUpdated $eventUpdated,
        StringLiteral $cdbXml,
        StringLiteral $cdbXmlNamespaceUri
    ) {
        return new self(
            $eventUpdated->getEventId(),
            $eventUpdated->getTime(),
            $eventUpdated->getAuthor(),
            $eventUpdated->getUrl(),
            $cdbXml,
            $cdbXmlNamespaceUri
        );
    }
}
