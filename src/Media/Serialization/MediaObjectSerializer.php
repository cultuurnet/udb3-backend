<?php

namespace CultuurNet\UDB3\Media\Serialization;

use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\MediaObject;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\SerializerInterface;

class MediaObjectSerializer implements SerializerInterface
{
    /**
     * @var IriGeneratorInterface
     */
    protected $iriGenerator;

    /**
     * MediaObjectSerializer constructor.
     * @param IriGeneratorInterface $iriGenerator
     */
    public function __construct(
        IriGeneratorInterface $iriGenerator
    ) {
        $this->iriGenerator = $iriGenerator;
    }

    /**
     * @param MediaObject|Image $mediaObject
     * @param string $format
     * @param array $context
     * @return array
     */
    public function serialize($mediaObject, $format, array $context = array())
    {
        if (!isset($format) || $format !== 'json-ld') {
            throw new UnsupportedException('Unsupported format, only json-ld is available.');
        };

        if ($mediaObject instanceof Image) {
            // Some Image objects have the 'application/octet-stream' mime-type, so we hardcode the @type to
            // 'schema:ImageObject' to make sure an Image does not get the @type 'schema:mediaObject'.
            $type = 'schema:ImageObject';
        } else {
            $type = $this->serializeMimeType($mediaObject->getMimeType());
        }

        $normalizedData = [
            '@id' => $this->iriGenerator->iri($mediaObject->getMediaObjectId()),
            '@type' => $type,
            'contentUrl' => (string) $mediaObject->getSourceLocation(),
            'thumbnailUrl' => (string) $mediaObject->getSourceLocation(),
            'description' => (string) $mediaObject->getDescription(),
            'copyrightHolder' => (string) $mediaObject->getCopyrightHolder(),
            'inLanguage' => (string) $mediaObject->getLanguage(),
        ];

        return $normalizedData;
    }

    public function serializeMimeType(MIMEType $mimeType)
    {
        $typeParts = explode('/', (string) $mimeType);
        $type = array_shift($typeParts);

        if ($type === 'image') {
            return 'schema:ImageObject';
        }

        if ((string) $mimeType === 'application/octet-stream') {
            return 'schema:mediaObject';
        }

        throw new UnsupportedException('Unsupported MIME-type "'. $mimeType .'"');
    }

    public function deserialize($data, $type, $format, array $context = array())
    {
        throw new \Exception('Deserialization currently not supported.');
    }
}
