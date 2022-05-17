<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\Place;

use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\Place\Place;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Geography\AddressNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class DummyLocationNormalizer implements NormalizerInterface
{
    private IriGeneratorInterface $iriGenerator;

    public function __construct(IriGeneratorInterface $iriGenerator)
    {
        $this->iriGenerator = $iriGenerator;
    }

    /**
     * @param Place $place
     */
    public function normalize($place, $format = null, array $context = []): array
    {
        $mainLanguageCode = $place->getMainLanguage()->getCode();
        $category = $place->getTerms()->toArray()[0];

        return [
            '@type' => 'Place',
            '@id' => $this->iriGenerator->iri($place->getId()->toString()),
            'mainLanguage' => $mainLanguageCode,
            'name' => [
                $mainLanguageCode => $place->getTitle()->getTranslation($place->getMainLanguage())->toString(),
            ],
            'terms' => [
                [
                    'id' => $category->getId()->toString(),
                    'label' => $category->getLabel()->toString(),
                    'domain' => $category->getDomain()->toString(),
                ],
            ],
            'calendarType' => CalendarType::permanent()->toString(),
            'status' => [
                'type' => StatusType::Available()->toString(),
            ],
            'bookingAvailability' => [
                'type' => BookingAvailabilityType::Available()->toString(),
            ],
            'address' => [
                $mainLanguageCode => (new AddressNormalizer())->normalize(
                    $place->getAddress()->getTranslation($place->getMainLanguage())
                ),
            ],
        ];
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data === Place::class;
    }
}
