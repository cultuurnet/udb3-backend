<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\ReadModel\JSONLD;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepositoryDecorator;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\StringLiteral;

final class PropertyPolyfillRepository extends DocumentRepositoryDecorator
{
    private ReadRepositoryInterface $labelReadRepository;

    public function __construct(DocumentRepository $repository, ReadRepositoryInterface $labelReadRepository)
    {
        parent::__construct($repository);
        $this->labelReadRepository = $labelReadRepository;
    }

    public function fetch(string $id, bool $includeMetadata = false): JsonDocument
    {
        $document = parent::fetch($id, $includeMetadata);
        $document = $this->polyfillNewProperties($document);
        $document = $this->fixDuplicateLabelVisibility($document);
        return $document;
    }

    private function polyfillNewProperties(JsonDocument $jsonDocument): JsonDocument
    {
        return $jsonDocument->applyAssoc(
            function (array $json) {
                $json = $this->polyfillImageType($json);
                $json = $this->polyfillImageInLanguage($json);
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

    private function polyfillImageInLanguage(array $json): array
    {
        if (!isset($json['images']) || !is_array($json['images'])) {
            return $json;
        }

        $json['images'] = array_map(
            function ($image) {
                if (is_array($image) && !isset($image['inLanguage']) && isset($image['language'])) {
                    $image['inLanguage'] = $image['language'];
                    unset($image['language']);
                }
                return $image;
            },
            $json['images']
        );

        return $json;
    }

    /**
     * Checks for labels that are both in "labels" and "hiddenLabels" and filters them out of the wrong property
     * depending on the label's visibility in the read repository.
     * It does not check every label to avoid performance issues, so only duplicate labels get fixed.
     */
    private function fixDuplicateLabelVisibility(JsonDocument $jsonDocument): JsonDocument
    {
        return $jsonDocument->applyAssoc(
            function (array $json) {
                if (!isset($json['labels'], $json['hiddenLabels']) ||
                    !is_array($json['labels']) ||
                    !is_array($json['hiddenLabels'])) {
                    return $json;
                }

                $toLowerCase = fn (string $label) => mb_strtolower($label, 'UTF-8');
                $lowerCasedLabels = array_map($toLowerCase, $json['labels']);
                $lowerCasedHiddenLabels = array_map($toLowerCase, $json['hiddenLabels']);
                $duplicates = array_intersect($lowerCasedLabels, $lowerCasedHiddenLabels);

                foreach ($duplicates as $duplicate) {
                    // Get the visibility from the read model, or if not found assume invisible to make sure that labels
                    // that should be hidden labels do not show up on publication channels (which would be worse than
                    // visible labels accidentally being hidden).
                    $readModel = $this->labelReadRepository->getByName(new StringLiteral($duplicate));
                    $visibility = $readModel ? $readModel->getVisibility() : Visibility::INVISIBLE();

                    // Filter the duplicate out of the property that it does not belong in.
                    $filterProperty = $visibility->sameAs(Visibility::VISIBLE()) ? 'hiddenLabels' : 'labels';
                    $json[$filterProperty] = array_values(
                        array_filter(
                            $json[$filterProperty],
                            fn ($labelName) => mb_strtolower($labelName, 'UTF-8') !== $duplicate
                        )
                    );
                }

                if (count($json['labels']) === 0) {
                    unset($json['labels']);
                }
                if (count($json['hiddenLabels']) === 0) {
                    unset($json['hiddenLabels']);
                }

                return $json;
            }
        );
    }
}
