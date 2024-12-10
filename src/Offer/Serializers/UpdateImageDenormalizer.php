<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Serializers;

use CultuurNet\UDB3\Event\Commands\UpdateImage as EventUpdateImage;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractUpdateImage;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Commands\UpdateImage as PlaceUpdateImage;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class UpdateImageDenormalizer implements DenormalizerInterface
{
    private OfferType $offerType;

    private string $offerId;

    private UUID $mediaObjectId;

    public function __construct(OfferType $offerType, string $offerId, UUID $mediaObjectId)
    {
        $this->offerType = $offerType;
        $this->offerId = $offerId;
        $this->mediaObjectId = $mediaObjectId;
    }

    public function denormalize($data, $class, $format = null, array $context = []): AbstractUpdateImage
    {
        if ($this->offerType->sameAs(OfferType::event())) {
            return new EventUpdateImage(
                $this->offerId,
                $this->mediaObjectId,
                $data['description'],
                new CopyrightHolder($data['copyrightHolder'])
            );
        } else {
            return new PlaceUpdateImage(
                $this->offerId,
                $this->mediaObjectId,
                $data['description'],
                new CopyrightHolder($data['copyrightHolder'])
            );
        }
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === AbstractUpdateImage::class;
    }
}
