<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Serializers;

use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Organizer\Commands\UpdateDescription;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class UpdateDescriptionDenormalizer implements DenormalizerInterface
{
    private string $organizerId;

    private Language $language;

    public function __construct(string $organizerId, Language $language)
    {
        $this->organizerId = $organizerId;
        $this->language = $language;
    }

    public function denormalize($data, $type, $format = null, array $context = []): UpdateDescription
    {
        return new UpdateDescription(
            $this->organizerId,
            new Description($data['description']),
            $this->language
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === UpdateDescription::class;
    }
}
