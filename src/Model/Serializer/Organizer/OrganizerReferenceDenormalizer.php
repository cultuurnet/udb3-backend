<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\Organizer;

use CultuurNet\UDB3\Model\Organizer\OrganizerIDParser;
use CultuurNet\UDB3\Model\Organizer\OrganizerReference;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class OrganizerReferenceDenormalizer implements DenormalizerInterface
{
    private OrganizerIDParser $organizerIDParser;

    public function __construct(OrganizerIDParser $organizerIDParser)
    {
        $this->organizerIDParser = $organizerIDParser;
    }

    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (!$this->supportsDenormalization($data, $class, $format)) {
            throw new UnsupportedException("OrganizerReferenceDenormalizer does not support {$class}.");
        }

        if (!is_array($data)) {
            throw new UnsupportedException('Organizer data should be an associative array.');
        }

        $organizerIdUrl = new Url($data['@id']);
        $organizerId = $this->organizerIDParser->fromUrl($organizerIdUrl);

        return OrganizerReference::createWithOrganizerId($organizerId);
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === OrganizerReference::class;
    }
}
