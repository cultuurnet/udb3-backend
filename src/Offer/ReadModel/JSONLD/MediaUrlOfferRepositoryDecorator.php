<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use CultuurNet\UDB3\Media\MediaUrlMapping;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepositoryDecorator;
use CultuurNet\UDB3\ReadModel\JsonDocument;

final class MediaUrlOfferRepositoryDecorator extends DocumentRepositoryDecorator
{
    private MediaUrlMapping $mediaUrlMapping;

    public function __construct(DocumentRepository $repository, MediaUrlMapping $mediaUrlMapping)
    {
        parent::__construct($repository);
        $this->mediaUrlMapping = $mediaUrlMapping;
    }

    public function fetch(string $id, bool $includeMetadata = false): JsonDocument
    {
        $document = parent::fetch($id, $includeMetadata);

        return $document->applyAssoc(
            function (array $json) {
                $json = $this->mapMediaUrls($json);
                return $this->mapMediaUrlsEmbeddedPlace($json);
            }
        );
    }

    private function mapMediaUrls(array $json): array
    {
        $json = $this->mapMediaObject($json);
        return $this->mapImage($json);
    }

    private function mapMediaUrlsEmbeddedPlace(array $json): array
    {
        if (!isset($json['location'])) {
            return $json;
        }

        $json['location'] = $this->mapMediaObject($json['location']);
        $json['location'] = $this->mapImage($json['location']);
        return $json;
    }

    private function mapMediaObject(array $json): array
    {
        if (!isset($json['mediaObject']) || !is_array($json['mediaObject'])) {
            return $json;
        }

        $json['mediaObject'] = array_map(
            function ($mediaObject) {
                if (!is_array($mediaObject) ||
                    !isset($mediaObject['contentUrl'], $mediaObject['thumbnailUrl'])) {
                    return $mediaObject;
                }

                $mediaObject['contentUrl'] = $this->mediaUrlMapping->getUpdatedUrl($mediaObject['contentUrl']);
                $mediaObject['thumbnailUrl'] = $this->mediaUrlMapping->getUpdatedUrl($mediaObject['thumbnailUrl']);

                return $mediaObject;
            },
            $json['mediaObject']
        );

        return $json;
    }

    private function mapImage(array $json): array
    {
        if (!isset($json['image']) || !is_string($json['image'])) {
            return $json;
        }

        $json['image'] = $this->mediaUrlMapping->getUpdatedUrl($json['image']);

        return $json;
    }
}
