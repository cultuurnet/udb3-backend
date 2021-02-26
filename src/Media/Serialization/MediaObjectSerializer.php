<?php

namespace CultuurNet\UDB3\Media\Serialization;

use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use Symfony\Component\Serializer\Exception\UnsupportedException;

class MediaObjectSerializer
{
    /**
     * @var IriGeneratorInterface
     */
    protected $iriGenerator;


    public function __construct(
        IriGeneratorInterface $iriGenerator
    ) {
        $this->iriGenerator = $iriGenerator;
    }

    public function serialize($mediaObject): array
    {
        if ($mediaObject instanceof Image) {
            // Some Image objects have the 'application/octet-stream' mime-type, so we hardcode the @type to
            // 'schema:ImageObject' to make sure an Image does not get the @type 'schema:mediaObject'.
            $type = 'schema:ImageObject';
        } else {
            $type = $this->serializeMimeType($mediaObject->getMimeType());
        }

        return  [
            '@id' => $this->iriGenerator->iri($mediaObject->getMediaObjectId()),
            '@type' => $type,
            'contentUrl' => (string) $mediaObject->getSourceLocation(),
            'thumbnailUrl' => (string) $mediaObject->getSourceLocation(),
            'description' => (string) $mediaObject->getDescription(),
            'copyrightHolder' => (string) $mediaObject->getCopyrightHolder(),
            'inLanguage' => (string) $mediaObject->getLanguage(),
        ];
    }

    public function serializeMimeType(MIMEType $mimeType): string
    {
        $typeParts = explode('/', (string) $mimeType);
        $type = array_shift($typeParts);

        if ($type === 'image') {
            return 'schema:ImageObject';
        }

        if ((string) $mimeType === 'application/octet-stream') {
            return 'schema:mediaObject';
        }

        throw new UnsupportedException('Unsupported MIME-type "' . $mimeType . '"');
    }
}
