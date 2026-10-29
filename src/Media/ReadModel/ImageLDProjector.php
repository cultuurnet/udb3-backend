<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\ReadModel;

use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Media\Events\MediaObjectAiGeneratedUpdated;
use CultuurNet\UDB3\Media\Events\MediaObjectCreated;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\AiGeneratedImageDescription;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;

final class ImageLDProjector implements EventListener
{
    /**
     * @uses applyMediaObjectCreated
     * @uses applyMediaObjectAiGeneratedUpdated
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

        $body = [
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

        if ($mediaObjectCreated->isAiGenerated() !== null) {
            $body['isAiGenerated'] = $mediaObjectCreated->isAiGenerated();
        }

        if ($mediaObjectCreated->isAiGenerated() === true) {
            $body['description'] = AiGeneratedImageDescription::addSuffix(
                $body['description'],
                $mediaObjectCreated->getLanguage()
            );
        }

        $jsonDocument = (new JsonDocument($id))->withAssocBody($body);

        $this->documentRepository->save($jsonDocument);
    }

    private function applyMediaObjectAiGeneratedUpdated(MediaObjectAiGeneratedUpdated $aiGeneratedUpdated): void
    {
        $id = $aiGeneratedUpdated->getMediaObjectId()->toString();

        try {
            $jsonDocument = $this->documentRepository->fetch($id);
        } catch (DocumentDoesNotExist $exception) {
            return;
        }

        $body = $jsonDocument->getAssocBody();

        $body['isAiGenerated'] = $aiGeneratedUpdated->isAiGenerated();
        $body['description'] = $aiGeneratedUpdated->getDescription()->toString();

        if ($aiGeneratedUpdated->isAiGenerated()) {
            $body['description'] = AiGeneratedImageDescription::addSuffix(
                $body['description'],
                $aiGeneratedUpdated->getLanguage()
            );
        }

        $this->documentRepository->save($jsonDocument->withAssocBody($body));
    }
}
