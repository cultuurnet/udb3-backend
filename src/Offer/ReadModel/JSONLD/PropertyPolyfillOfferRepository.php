<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Online\AttendanceMode;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailability;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepositoryDecorator;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\SameAsForUitInVlaanderen;

final class PropertyPolyfillOfferRepository extends DocumentRepositoryDecorator
{
    private ReadRepositoryInterface $labelReadRepository;

    private OfferType $offerType;

    public function __construct(
        DocumentRepository $repository,
        ReadRepositoryInterface $labelReadRepository,
        OfferType $offerType
    ) {
        parent::__construct($repository);
        $this->labelReadRepository = $labelReadRepository;
        $this->offerType = $offerType;
    }

    public function fetch(string $id, bool $includeMetadata = false): JsonDocument
    {
        $document = parent::fetch($id, $includeMetadata);
        $document = $this->polyfillNewProperties($document);
        $document = $this->removeObsoleteProperties($document);
        $document = $this->removeNullLabels($document);
        $document = $this->removeThemes($document);
        $document = $this->removeMainImageWhenMediaObjectIsEmpty($document);
        $document = $this->removeActorType($document);
        $document = $this->removeBookingInfoWhenEmpty($document);
        return $this->fixDuplicateLabelVisibility($document);
    }

    private function polyfillNewProperties(JsonDocument $jsonDocument): JsonDocument
    {
        return $jsonDocument->applyAssoc(
            function (array $json) {
                $json = $this->polyfillMediaObjectId($json);
                $json = $this->polyfillStatus($json);
                $json = $this->polyfillBookingAvailability($json);
                $json = $this->polyfillSubEventProperties($json);
                $json = $this->polyfillEmbeddedPlaceStatus($json);
                $json = $this->polyfillEmbeddedPlaceBookingAvailability($json);
                $json = $this->polyfillTypicalAgeRange($json);

                if ($this->offerType->sameAs(OfferType::event())) {
                    $json = $this->polyfillAttendanceMode($json);
                }

                return $this->polyfillBrokenSameAs($json);
            }
        );
    }

    private function polyfillMediaObjectId(array $json): array
    {
        if (!isset($json['mediaObject']) || !is_array($json['mediaObject'])) {
            return $json;
        }

        $json['mediaObject'] = array_map(
            function ($mediaObject) {
                if (!is_array($mediaObject) || isset($mediaObject['id']) || !isset($mediaObject['@id'])) {
                    return $mediaObject;
                }
                $urlParts = explode('/', $mediaObject['@id']);
                $id = array_pop($urlParts);
                $mediaObject['id'] = $id;
                return $mediaObject;
            },
            $json['mediaObject']
        );

        return $json;
    }

    private function polyfillStatus(array $json): array
    {
        // Fixing the previous status format without the type property.
        if (isset($json['status']) && !isset($json['status']['type'])) {
            $json['status'] = [
                'type' => $json['status'],
            ];
        }

        if (!isset($json['status'])) {
            $json['status'] = [
                'type' => StatusType::available()->toString(),
            ];
        }

        return $json;
    }

    private function polyfillAttendanceMode(array $json): array
    {
        if (!isset($json['attendanceMode'])) {
            $json['attendanceMode'] = AttendanceMode::offline()->toString();
        }

        return $json;
    }

    private function polyfillBookingAvailability(array $json): array
    {
        if (!isset($json['bookingAvailability'])) {
            $json['bookingAvailability'] = BookingAvailability::available()->serialize();
        }

        return $json;
    }

    private function polyfillTypicalAgeRange(array $json): array
    {
        if (!isset($json['typicalAgeRange'])) {
            $json['typicalAgeRange'] = '-';
        }

        return $json;
    }

    private function polyfillSubEventProperties(array $json): array
    {
        if (!isset($json['subEvent']) || !is_array($json['subEvent'])) {
            return $json;
        }

        $json['subEvent'] = array_map(
            function (array $subEvent, int $index) {
                return array_merge(
                    [
                        'id' => $index,
                        'status' => [
                            'type' => StatusType::available()->toString(),
                        ],
                        'bookingAvailability' => BookingAvailability::available()->serialize(),
                    ],
                    $subEvent
                );
            },
            $json['subEvent'],
            range(0, count($json['subEvent']) - 1)
        );

        return $json;
    }

    private function polyfillEmbeddedPlaceStatus(array $json): array
    {
        if (!isset($json['location'])) {
            return $json;
        }

        if (isset($json['location']['status']) && !isset($json['location']['status']['type'])) {
            $json['location']['status'] = [
                'type' => $json['location']['status'],
            ];
        }

        if (!isset($json['location']['status'])) {
            $json['location']['status'] = [
                'type' => StatusType::available()->toString(),
            ];
        }

        return $json;
    }

    private function polyfillEmbeddedPlaceBookingAvailability(array $json): array
    {
        if (!isset($json['location'])) {
            return $json;
        }

        if (!isset($json['location']['bookingAvailability'])) {
            $json['location']['bookingAvailability'] = BookingAvailability::available()->serialize();
        }

        return $json;
    }

    private function polyfillBrokenSameAs(array $json): array
    {
        if (!isset($json['sameAs'])) {
            return $json;
        }

        $urlParts = explode('/', $json['@id']);
        $id = array_pop($urlParts);
        $name = $json['name']['nl'] ?? ($json['name'][$json['mainLanguage']] ?? current($json['name']));

        $json['sameAs'] = (new SameAsForUitInVlaanderen())->generateSameAs($id, $name);

        return $json;
    }

    private function removeObsoleteProperties(JsonDocument $jsonDocument): JsonDocument
    {
        $obsoleteProperties = ['calendarSummary'];

        return $jsonDocument->applyAssoc(
            function (array $json) use ($obsoleteProperties) {
                return array_diff_key($json, array_flip($obsoleteProperties));
            }
        );
    }

    private function removeNullLabels(JsonDocument $jsonDocument): JsonDocument
    {
        return $jsonDocument->applyAssoc(
            function (array $json) {
                $filterNullLabels = static function (array $json, string $propertyName): array {
                    if (!isset($json[$propertyName]) || !is_array($json[$propertyName])) {
                        return $json;
                    }
                    $json[$propertyName] = array_values(
                        array_filter($json[$propertyName], fn ($label) => $label !== null)
                    );
                    if ($json[$propertyName] === []) {
                        unset($json[$propertyName]);
                    }
                    return $json;
                };

                $json = $filterNullLabels($json, 'labels');
                return $filterNullLabels($json, 'hiddenLabels');
            }
        );
    }

    private function removeThemes(JsonDocument $jsonDocument): JsonDocument
    {
        if ($this->offerType->sameAs(OfferType::event())) {
            return $jsonDocument;
        }
        return $jsonDocument->applyAssoc(
            function (array $json) {
                if (!isset($json['terms']) || !is_array($json['terms'])) {
                    return $json;
                }

                $json['terms'] = array_values(
                    array_filter(
                        $json['terms'],
                        fn ($terms) => $terms['domain'] !== 'theme'
                    )
                );

                return $json;
            }
        );
    }

    private function removeMainImageWhenMediaObjectIsEmpty(JsonDocument $jsonDocument): JsonDocument
    {
        return $jsonDocument->applyAssoc(
            function (array $json) {
                if (isset($json['mediaObject']) && is_array($json['mediaObject'])) {
                    return $json;
                }

                unset($json['image']);
                return $json;
            }
        );
    }

    private function removeActorType(JsonDocument $jsonDocument): JsonDocument
    {
        return $jsonDocument->applyAssoc(
            function (array $json) {
                if (!isset($json['terms']) || !is_array($json['terms'])) {
                    return $json;
                }

                $json['terms'] = array_values(
                    array_filter(
                        $json['terms'],
                        fn ($terms) => $terms['domain'] !== 'actortype'
                    )
                );

                return $json;
            }
        );
    }

    private function removeBookingInfoWhenEmpty(JsonDocument $jsonDocument): JsonDocument
    {
        return $jsonDocument->applyAssoc(
            function (array $json) {
                if (!isset($json['bookingInfo']) || !is_array($json['bookingInfo'])) {
                    return $json;
                }

                if ($json['bookingInfo'] === []) {
                    unset($json['bookingInfo']);
                }

                return $json;
            }
        );
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
                    $readModel = $this->labelReadRepository->getByName($duplicate);
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
