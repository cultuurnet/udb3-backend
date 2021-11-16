<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use CultuurNet\UDB3\Media\MediaUrlRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepositoryDecorator;
use CultuurNet\UDB3\ReadModel\JsonDocument;

final class MediaUrlOfferRepositoryDecorator extends DocumentRepositoryDecorator
{
    private MediaUrlRepository $mediaUrlRepository;

    public function __construct(DocumentRepository $repository, MediaUrlRepository $mediaUrlRepository)
    {
        parent::__construct($repository);
        $this->mediaUrlRepository = $mediaUrlRepository;
    }

    public function fetch(string $id, bool $includeMetadata = false): JsonDocument
    {
        $document = parent::fetch($id, $includeMetadata);
        $document = $document->applyAssoc(
            function (array $json) {
                if (!isset($json['mediaObject']) || !is_array($json['mediaObject'])) {
                    return $json;
                }

                $json['mediaObject'] = array_map(
                    function ($mediaObject) {
                        if (!is_array($mediaObject) ||
                            !isset($mediaObject['contentUrl'], $mediaObject['thumbnailUrl'])) {
                            return $mediaObject;
                        }

                        $mediaObject['contentUrl'] = $this->mediaUrlRepository->getUpdatedUrl($mediaObject['contentUrl']);
                        $mediaObject['thumbnailUrl'] = $this->mediaUrlRepository->getUpdatedUrl($mediaObject['thumbnailUrl']);

                        return $mediaObject;
                    },
                    $json['mediaObject']
                );

                if (!isset($json['image']) || !is_string($json['image'])) {
                    return $json;
                }
                $json['image'] = $this->mediaUrlRepository->getUpdatedUrl($json['image']);

                return $json;
            }
        );
        return $document;
    }
}
