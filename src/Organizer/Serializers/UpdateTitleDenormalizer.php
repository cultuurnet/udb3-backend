<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Serializers;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Organizer\Commands\UpdateTitle;
use CultuurNet\UDB3\Language as LegacyLanguage;
use CultuurNet\UDB3\Title;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class UpdateTitleDenormalizer implements DenormalizerInterface
{
    private string $organizerId;

    private Language $language;

    public function __construct(string $organizerId, Language $language)
    {
        $this->organizerId = $organizerId;
        $this->language = $language;
    }

    public function denormalize($data, $type, $format = null, array $context = []): UpdateTitle
    {
        return new UpdateTitle(
            $this->organizerId,
            new Title($data['name']),
            LegacyLanguage::fromUdb3ModelLanguage($this->language)
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === UpdateTitle::class;
    }
}
