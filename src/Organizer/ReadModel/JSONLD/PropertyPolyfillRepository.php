<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\ReadModel\JSONLD;

use CultuurNet\UDB3\ReadModel\DocumentRepositoryDecorator;
use CultuurNet\UDB3\ReadModel\JsonDocument;

final class PropertyPolyfillRepository extends DocumentRepositoryDecorator
{
    public function fetch(string $id, bool $includeMetadata = false): JsonDocument
    {
        $document = parent::fetch($id, $includeMetadata);
        $document = $this->polyfillNewProperties($document);
        return $document;
    }

    private function polyfillNewProperties(JsonDocument $jsonDocument): JsonDocument
    {
        return $jsonDocument->applyAssoc(
            function (array $json) {
                $json = $this->polyfillImageType($json);
                return $json;
            }
        );
    }

    private function polyfillImageType(array $json): array
    {
        if (!isset($json['images']) || !is_array($json['images'])) {
            return $json;
        }

        $json['images'] = array_map(
            function ($image) {
                if (is_array($image) && !isset($image['@type'])) {
                    $image['@type'] = 'schema:ImageObject';
                }
                return $image;
            },
            $json['images']
        );

        return $json;
    }
}
