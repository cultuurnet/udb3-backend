<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Offer;

use CultuurNet\UDB3\Event\Commands\UpdateTypicalAgeRange as EventUpdateTypicalAgeRange;
use CultuurNet\UDB3\Model\ValueObject\Audience\AgeRange;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateTypicalAgeRange;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Commands\UpdateTypicalAgeRange as PlaceUpdateTypicalAgeRange;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class UpdateTypicalAgeRangeDenormalizer implements DenormalizerInterface
{
    private OfferType $offerType;

    private string $offerId;

    public function __construct(OfferType $offerType, string $offerId)
    {
        $this->offerType = $offerType;
        $this->offerId = $offerId;
    }

    public function denormalize($data, $class, $format = null, array $context = []): AbstractUpdateTypicalAgeRange
    {
        $ageRange = AgeRange::fromString($data['typicalAgeRange']);

        if ($this->offerType->sameAs(OfferType::event())) {
            return new EventUpdateTypicalAgeRange(
                $this->offerId,
                $ageRange
            );
        }

        return new PlaceUpdateTypicalAgeRange(
            $this->offerId,
            $ageRange
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === AbstractUpdateTypicalAgeRange::class;
    }
}
