<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Serializers;

use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Organizer\Commands\UpdateEducationalDescription;
use InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class UpdateEducationalDescriptionDenormalizer implements DenormalizerInterface
{
    private string $organizerId;

    private Language $language;

    public function __construct(string $organizerId, Language $language)
    {
        $this->organizerId = $organizerId;
        $this->language = $language;
    }

    public function denormalize($data, $class='', $format = null, array $context = []): UpdateEducationalDescription
    {
        if(!isset($data['educationalDescription'])) {
            throw new InvalidArgumentException('Missing required parameter educationalDescription');
        }

        return new UpdateEducationalDescription(
            $this->organizerId,
            new Description($data['educationalDescription']),
            $this->language
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === UpdateEducationalDescription::class;
    }
}
