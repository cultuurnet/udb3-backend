<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF\Editor;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Contact\BookingInfoNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\RDF\NodeUri\ResourceFactory\RdfResourceFactory;
use EasyRdf\Resource;

final class ContactPointEditor
{
    private const TYPE_CONTACT_POINT = 'schema:ContactPoint';

    private const PROPERTY_CONTACT_POINT = 'schema:contactPoint';
    private const PROPERTY_CONTACT_POINT_URL = 'schema:url';
    private const PROPERTY_CONTACT_POINT_EMAIL = 'schema:email';
    private const PROPERTY_CONTACT_POINT_PHONE = 'schema:telephone';
    private RdfResourceFactory $rdfResourceFactory;

    public function __construct(RdfResourceFactory $rdfResourceFactory)
    {
        $this->rdfResourceFactory = $rdfResourceFactory;
    }

    public function setContactPoint(Resource $resource, ContactPoint $contactPoint): void
    {
        foreach ($contactPoint->getUrls() as $url) {
            $urlResource = $this->rdfResourceFactory->create($resource, self::TYPE_CONTACT_POINT, [
                'url' => $url->toString(),
            ]);

            $urlResource->addLiteral(self::PROPERTY_CONTACT_POINT_URL, $url->toString());
            $resource->add(self::PROPERTY_CONTACT_POINT, $urlResource);
        }

        foreach ($contactPoint->getEmailAddresses() as $email) {
            $emailResource = $this->rdfResourceFactory->create($resource, self::TYPE_CONTACT_POINT, [
                'url' => $email->toString(),
            ]);
            $emailResource->addLiteral(self::PROPERTY_CONTACT_POINT_EMAIL, $email->toString());
            $resource->add(self::PROPERTY_CONTACT_POINT, $emailResource);
        }

        foreach ($contactPoint->getTelephoneNumbers() as $phone) {
            $phoneResource = $this->rdfResourceFactory->create($resource, self::TYPE_CONTACT_POINT, [
                'url' => $phone->toString(),
            ]);
            $phoneResource->addLiteral(self::PROPERTY_CONTACT_POINT_PHONE, $phone->toString());
            $resource->add(self::PROPERTY_CONTACT_POINT, $phoneResource);
        }
    }

    public function setBookingInfo(Resource $resource, BookingInfo $bookingInfo): void
    {
        $contactPoint = $this->rdfResourceFactory->create(
            $resource,
            self::TYPE_CONTACT_POINT,
            (new BookingInfoNormalizer())->normalize($bookingInfo)
        );

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
