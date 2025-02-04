<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\ReadModel;

use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Media\Events\MediaObjectCreated;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;

final class ImageLDProjector implements EventListener
{
    /**
     * @uses applyMediaObjectCreated
     */
    use DelegateEventHandlingToSpecificMethodTrait {
        DelegateEventHandlingToSpecificMethodTrait::handle as handleMethodSpecificEvents;
    }

    private DocumentRepository $documentRepository;

    private IriGeneratorInterface $iriGenerator;

    private IriGeneratorInterface $thumbnailIriGenerator;

    public function __construct(
        DocumentRepository $documentRepository,
        IriGeneratorInterface $iriGenerator,
        IriGeneratorInterface $thumbnailIriGenerator
    ) {
        $this->documentRepository = $documentRepository;
        $this->iriGenerator = $iriGenerator;
        $this->thumbnailIriGenerator = $thumbnailIriGenerator;
    }

    private function applyMediaObjectCreated(MediaObjectCreated $mediaObjectCreated): void
    {
        $id = $mediaObjectCreated->getMediaObjectId()->toString();
        $fileName = $id . '.' . $mediaObjectCreated->getMimeType()->getFilenameExtension();

        $jsonDocument = new JsonDocument($id);

        $image =  [
            '@id' => $this->iriGenerator->iri($id),
            '@type' => 'schema:ImageObject',
            'encodingFormat' => $mediaObjectCreated->getMimeType()->toString(),
            'id' => $id,
            'contentUrl' => $this->thumbnailIriGenerator->iri($fileName),
            'thumbnailUrl' => $this->thumbnailIriGenerator->iri($fileName),
            'description' => $mediaObjectCreated->getDescription()->toString(),
            'copyrightHolder' => $mediaObjectCreated->getCopyrightHolder()->toString(),
            'inLanguage' => $mediaObjectCreated->getLanguage()->toString(),
        ];

        $jsonDocument = $jsonDocument->withAssocBody($image);

        $this->documentRepository->save($jsonDocument);
    }
}
