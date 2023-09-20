<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF\Editor;

use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use EasyRdf\Resource;

final class ContactPointEditor
{
    private const TYPE_CONTACT_POINT = 'schema:ContactPoint';

    private const PROPERTY_CONTACT_POINT = 'schema:contactPoint';
    private const PROPERTY_CONTACT_POINT_URL = 'schema:url';
    private const PROPERTY_CONTACT_POINT_EMAIL = 'schema:email';
    private const PROPERTY_CONTACT_POINT_PHONE = 'schema:telephone';

    public function setContactPoint(Resource $resource, ContactPoint $contactPoint): void
    {
        foreach ($contactPoint->getUrls() as $url) {
            $urlResource = $resource->getGraph()->newBNode([self::TYPE_CONTACT_POINT]);
            $urlResource->addLiteral(self::PROPERTY_CONTACT_POINT_URL, $url->toString());
            $resource->add(self::PROPERTY_CONTACT_POINT, $urlResource);
        }

        foreach ($contactPoint->getEmailAddresses() as $email) {
            $urlResource = $resource->getGraph()->newBNode([self::TYPE_CONTACT_POINT]);
            $urlResource->addLiteral(self::PROPERTY_CONTACT_POINT_EMAIL, $email->toString());
            $resource->add(self::PROPERTY_CONTACT_POINT, $urlResource);
        }

        foreach ($contactPoint->getTelephoneNumbers() as $phone) {
            $urlResource = $resource->getGraph()->newBNode([self::TYPE_CONTACT_POINT]);
            $urlResource->addLiteral(self::PROPERTY_CONTACT_POINT_PHONE, $phone->toString());
            $resource->add(self::PROPERTY_CONTACT_POINT, $urlResource);
        }
    }
}
