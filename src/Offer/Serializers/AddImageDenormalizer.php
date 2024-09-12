<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Serializers;

use CultuurNet\UDB3\Event\Commands\AddImage as EventAddImage;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractAddImage;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Commands\AddImage as PlaceAddImage;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class AddImageDenormalizer implements DenormalizerInterface
{
    private OfferType $offerType;

    private string $offerId;

    public function __construct(OfferType $offerType, string $offerId)
    {
        $this->offerType = $offerType;
        $this->offerId = $offerId;
    }

    public function denormalize($data, $class, $format = null, array $context = []): AbstractAddImage
    {
        if ($this->offerType->sameAs(OfferType::event())) {
            return new EventAddImage(
                $this->offerId,
                new UUID($data['mediaObjectId'])
            );
        } else {
            return new PlaceAddImage(
                $this->offerId,
                new UUID($data['mediaObjectId'])
            );
        }
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === AbstractAddImage::class;
    }
}
