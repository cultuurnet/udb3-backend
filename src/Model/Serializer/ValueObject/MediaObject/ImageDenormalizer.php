<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUIDParser;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Image;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObjectIDParser;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class ImageDenormalizer implements DenormalizerInterface
{
    /**
     * @var UUIDParser
     */
    private $imageIdParser;

    public function __construct(UUIDParser $imageIdParser = null)
    {
        if (!$imageIdParser) {
            $imageIdParser = new MediaObjectIDParser();
        }
        $this->imageIdParser = $imageIdParser;
    }

    /**
     * @inheritdoc
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (!$this->supportsDenormalization($data, $class, $format)) {
            throw new UnsupportedException("ImageDenormalizer does not support {$class}.");
        }

        if (!is_array($data)) {
            throw new UnsupportedException('Images data should be an array.');
        }

        $id = $this->imageIdParser->fromUrl(new Url($data['@id']));
        $description = new Description($data['description']);
        $copyrightHolder = new CopyrightHolder($data['copyrightHolder']);
        $language = new Language($data['inLanguage']);

        return new Image($id, $language, $description, $copyrightHolder);
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === Image::class;
    }
}
