<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Serializers;

use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Commands\UpdateTitle;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class UpdateTitleDenormalizer implements DenormalizerInterface
{
    private string $offerId;

    private Language $language;

    public function __construct(string $offerId, Language $language)
    {
        $this->offerId = $offerId;
        $this->language = $language;
    }

    public function denormalize($data, $type, $format = null, array $context = []): UpdateTitle
    {
        return new UpdateTitle(
            $this->offerId,
            $this->language,
            new Title($data['name'])
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === UpdateTitle::class;
    }
}
