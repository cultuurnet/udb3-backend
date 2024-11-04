<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultureFeed_Cdb_Xml;
use CultuurNet\UDB3\Event\EventEvent;
use CultuurNet\UDB3\EventSourcing\ConvertsToGranularEvents;
use CultuurNet\UDB3\EventSourcing\MainLanguageDefined;
use CultuurNet\UDB3\HasCdbXmlTrait;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

final class EventImportedFromUDB2 extends EventEvent implements EventCdbXMLInterface, MainLanguageDefined, ConvertsToGranularEvents
{
    use HasCdbXmlTrait;
    use EventFromUDB2;

    public function __construct(string $eventId, string $cdbXml, string $cdbXmlNamespaceUri)
    {
        parent::__construct($eventId);
        $this->setCdbXml($cdbXml);
        $this->setCdbXmlNamespaceUri($cdbXmlNamespaceUri);
    }

    public function getMainLanguage(): Language
    {
        // Events imported from UDB2 always have the main language NL.
        return new Language('nl');
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'cdbxml' => $this->getCdbXml(),
            'cdbXmlNamespaceUri' => $this->getCdbXmlNamespaceUri(),
        ];
    }

    public static function deserialize(array $data): EventImportedFromUDB2
    {
        $data += [
            'cdbXmlNamespaceUri' => CultureFeed_Cdb_Xml::namespaceUriForVersion('3.2'),
        ];
        return new self(
            $data['event_id'],
            $data['cdbxml'],
            $data['cdbXmlNamespaceUri']
        );
    }
}
