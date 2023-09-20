<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF\Editor;

use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
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

    public function setBookingInfo(Resource $resource, BookingInfo $bookingInfo): void
    {
        $contactPoint = $resource->getGraph()->newBNode([self::TYPE_CONTACT_POINT]);

        if ($bookingInfo->getWebsite()) {
            $contactPoint->addLiteral(
                self::PROPERTY_CONTACT_POINT_URL,
                $bookingInfo->getWebsite()->getUrl()->toString()
            );
        }

        if ($bookingInfo->getTelephoneNumber()) {
            $contactPoint->addLiteral(
                self::PROPERTY_CONTACT_POINT_PHONE,
                $bookingInfo->getTelephoneNumber()->toString()
            );
        }

        if ($bookingInfo->getEmailAddress()) {
            $contactPoint->addLiteral(
                self::PROPERTY_CONTACT_POINT_EMAIL,
                $bookingInfo->getEmailAddress()->toString()
            );
        }

        $resource->add(self::PROPERTY_CONTACT_POINT, $contactPoint);
    }
}
