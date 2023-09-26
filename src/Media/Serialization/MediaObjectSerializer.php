<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\Serialization;

use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\MediaObject;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use Symfony\Component\Serializer\Exception\UnsupportedException;

class MediaObjectSerializer
{
    protected IriGeneratorInterface $iriGenerator;

    public function __construct(
        IriGeneratorInterface $iriGenerator
    ) {
        $this->iriGenerator = $iriGenerator;
    }

    /**
     * @param Image|MediaObject $mediaObject
     */
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
            '@id' => $this->iriGenerator->iri($mediaObject->getMediaObjectId()->toString()),
            '@type' => $type,
            'id' => $mediaObject->getMediaObjectId()->toString(),
            'contentUrl' => $mediaObject->getSourceLocation()->toString(),
            'thumbnailUrl' => $mediaObject->getSourceLocation()->toString(),
            'description' => $mediaObject->getDescription()->toString(),
            'copyrightHolder' => $mediaObject->getCopyrightHolder()->toString(),
            'inLanguage' => $mediaObject->getLanguage()->toString(),
        ];
    }

    public function serializeMimeType(MIMEType $mimeType): string
    {
        $typeParts = explode('/', $mimeType->toString());
        $type = array_shift($typeParts);

        if ($type === 'image') {
            return 'schema:ImageObject';
        }

        if ($mimeType->toString() === 'application/octet-stream') {
            return 'schema:mediaObject';
        }

        throw new UnsupportedException('Unsupported MIME-type "' . $mimeType->toString() . '"');
    }
}
