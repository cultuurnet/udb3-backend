<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\ReadModel\DocumentRepositoryDecorator;
use CultuurNet\UDB3\ReadModel\JsonDocument;

final class NewPropertyPolyfillOfferRepository extends DocumentRepositoryDecorator
{
    public function fetch(string $id, bool $includeMetadata = false): JsonDocument
    {
        $document = parent::fetch($id, $includeMetadata);
        return $this->polyfillNewProperties($document);
    }

    public function get(string $id, bool $includeMetadata = false): ?JsonDocument
    {
        $document = parent::get($id, $includeMetadata);

        if (!is_null($document)) {
            return $document;
        }

        return $this->polyfillNewProperties($document);
    }

    private function polyfillNewProperties(JsonDocument $jsonDocument): JsonDocument
    {
        return $jsonDocument->applyAssoc(
            function (array $json) {
                $json = $this->polyfillStatus($json);
                $json = $this->polyfillSubEventStatus($json);
                return $json;
            }
        );
    }

    private function polyfillStatus(array $json): array
    {
        if (!isset($json['status'])) {
            $json['status'] = StatusType::available()->toNative();
        }
        return $json;
    }

    private function polyfillSubEventStatus(array $json): array {
        if (!isset($json['subEvent']) || !is_array($json['subEvent'])) {
            return $json;
        }

        $json['subEvent'] = array_map(
            function (array $subEvent) {
                return array_merge(
                    [
                        'status' => [
                            'type' => StatusType::available()->toNative(),
                        ],
                    ],
                    $subEvent
                );
            },
            $json['subEvent']
        );

        return $json;
    }
}
