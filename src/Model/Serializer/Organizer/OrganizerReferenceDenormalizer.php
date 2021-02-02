<?php

namespace CultuurNet\UDB3\Model\Serializer\Organizer;

use CultuurNet\UDB3\Model\Organizer\Organizer;
use CultuurNet\UDB3\Model\Organizer\OrganizerIDParser;
use CultuurNet\UDB3\Model\Organizer\OrganizerReference;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUIDParser;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class OrganizerReferenceDenormalizer implements DenormalizerInterface
{
    /**
     * @var OrganizerIDParser
     */
    private $organizerIDParser;

    /**
     * @var DenormalizerInterface
     */
    private $organizerDenormalizer;

    /**
     * @param UUIDParser|null $organizerIDParser
     * @param DenormalizerInterface|null $organizerDenormalizer
     */
    public function __construct(
        UUIDParser $organizerIDParser = null,
        DenormalizerInterface $organizerDenormalizer = null
    ) {
        if (!$organizerIDParser) {
            $organizerIDParser = new OrganizerIDParser();
        }

        if (!$organizerDenormalizer) {
            $organizerDenormalizer = new OrganizerDenormalizer();
        }

        $this->organizerIDParser = $organizerIDParser;
        $this->organizerDenormalizer = $organizerDenormalizer;
    }

    public function denormalize($data, $class, $format = null, array $context = array())
    {
        if (!$this->supportsDenormalization($data, $class, $format)) {
            throw new UnsupportedException("OrganizerReferenceDenormalizer does not support {$class}.");
        }

        if (!is_array($data)) {
            throw new UnsupportedException('Organizer data should be an associative array.');
        }

        $organizerIdUrl = new Url($data['@id']);
        $organizerId = $this->organizerIDParser->fromUrl($organizerIdUrl);
        $organizer = null;
        if (count($data) > 1) {
            try {
                $organizer = $this->organizerDenormalizer->denormalize($data, Organizer::class);
            } catch (\Exception $e) {
                $organizer = null;
            }
        }

        if ($organizer) {
            return OrganizerReference::createWithEmbeddedOrganizer($organizer);
        } else {
            return OrganizerReference::createWithOrganizerId($organizerId);
        }
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === OrganizerReference::class;
    }
}
