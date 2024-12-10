<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Offer;

use CultuurNet\UDB3\Event\Commands\SelectMainImage as EventSelectMainImage;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Media\MediaObjectNotFoundException;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractSelectMainImage;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Commands\SelectMainImage as PlaceSelectMainImage;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class SelectMainImageDenormalizer implements DenormalizerInterface
{
    private MediaManagerInterface $mediaManager;

    private OfferType $offerType;

    private string $offerId;

    public function __construct(MediaManagerInterface $mediaManager, OfferType $offerType, string $offerId)
    {
        $this->mediaManager = $mediaManager;
        $this->offerType = $offerType;
        $this->offerId = $offerId;
    }

    public function denormalize($data, $class, $format = null, array $context = []): AbstractSelectMainImage
    {
        try {
            $image = $this->mediaManager->getImage(new UUID($data['mediaObjectId']));
        } catch (MediaObjectNotFoundException $exception) {
            throw ApiProblem::imageNotFound($data['mediaObjectId']);
        }

        if ($this->offerType->sameAs(OfferType::event())) {
            return new EventSelectMainImage(
                $this->offerId,
                $image
            );
        }

        return new PlaceSelectMainImage(
            $this->offerId,
            $image
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === AbstractSelectMainImage::class;
    }
}
