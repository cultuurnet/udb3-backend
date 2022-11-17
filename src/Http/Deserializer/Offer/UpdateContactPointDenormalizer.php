<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Offer;

use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Event\Commands\UpdateContactPoint as EventUpdateContactPoint;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateContactPoint;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Commands\UpdateContactPoint as PlaceUpdateContactPoint;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class UpdateContactPointDenormalizer implements DenormalizerInterface
{
    private OfferType $offerType;

    private string $offerId;

    public function __construct(OfferType $offerType, string $offerId)
    {
        $this->offerType = $offerType;
        $this->offerId = $offerId;
    }

    public function denormalize($data, $class, $format = null, array $context = []): AbstractUpdateContactPoint
    {
        $contactPoint = new ContactPoint(
            $data['phone'],
            $data['email'],
            $data['url'],
        );

        if ($this->offerType->sameAs(OfferType::event())) {
            return new EventUpdateContactPoint(
                $this->offerId,
                $contactPoint
            );
        }
        return new PlaceUpdateContactPoint(
            $this->offerId,
            $contactPoint
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === AbstractUpdateContactPoint::class;
    }
}
