<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\Event\Events;

use CultuurNet\UDB3\Cdb\CdbXmlContainerInterface;
use CultuurNet\UDB3\HasCdbXmlTrait;
use CultuurNet\UDB3\UDB2\DomainEvents\EventCreated;
use DateTimeImmutable;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

/**
 * @file
 */
class EventCreatedEnrichedWithCdbXml extends EventCreated implements CdbXmlContainerInterface
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

    public static function fromEventCreated(
        EventCreated $eventCreated,
        StringLiteral $cdbXml,
        StringLiteral $cdbXmlNamespaceUri
    ) {
        return new self(
            $eventCreated->getEventId(),
            $eventCreated->getTime(),
            $eventCreated->getAuthor(),
            $eventCreated->getUrl(),
            $cdbXml,
            $cdbXmlNamespaceUri
        );
    }
}
