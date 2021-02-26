<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\Event;

use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\UDB2\XML\XMLValidationError;
use CultuurNet\UDB3\UDB2\XML\XMLValidationServiceInterface;
use ValueObjects\StringLiteral\StringLiteral;

class EventXMLValidatorService implements XMLValidationServiceInterface
{
    /**
     * @var StringLiteral
     */
    private $cdbXmlNamespaceUri;

    public function __construct(StringLiteral $cdbXmlNamespaceUri)
    {
        $this->cdbXmlNamespaceUri = $cdbXmlNamespaceUri;
    }

    /**
     * @inheritdoc
     */
    public function validate($xml)
    {
        $errors = [];

        $cdbXmlEvent = EventItemFactory::createEventFromCdbXml(
            (string) $this->cdbXmlNamespaceUri,
            (string) $xml
        );

        if ($cdbXmlEvent &&
            $cdbXmlEvent->getLocation() &&
            $cdbXmlEvent->getOrganiser() &&
            $cdbXmlEvent->getLocation()->getExternalId() === null &&
            $cdbXmlEvent->getOrganiser()->getExternalId() === null &&
            ($cdbXmlEvent->getLocation()->getCdbid() != null || $cdbXmlEvent->getOrganiser()->getCdbid() != null) &&
            $cdbXmlEvent->getLocation()->getCdbid() === $cdbXmlEvent->getOrganiser()->getCdbid()) {
            $message = 'The event with cdbid ' . $cdbXmlEvent->getCdbId();
            $message .= ', has a location and place with the same cdbid ' . $cdbXmlEvent->getLocation()->getCdbid();

            $errors[] = new XMLValidationError($message, 0, 0);
        }

        return $errors;
    }
}
