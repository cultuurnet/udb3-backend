<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Virtual\AttendanceMode;
use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailability;
use CultuurNet\UDB3\ReadModel\DocumentRepositoryDecorator;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\SameAsForUitInVlaanderen;

final class NewPropertyPolyfillOfferRepository extends DocumentRepositoryDecorator
{
    public function fetch(string $id, bool $includeMetadata = false): JsonDocument
    {
        $document = parent::fetch($id, $includeMetadata);
        $document = $this->polyfillNewProperties($document);
        $document = $this->removeObsoleteProperties($document);
        return $document;
    }

    private function polyfillNewProperties(JsonDocument $jsonDocument): JsonDocument
    {
        return $jsonDocument->applyAssoc(
            function (array $json) {
                $json = $this->polyfillMediaObjectId($json);
                $json = $this->polyfillStatus($json);
                $json = $this->polyfillAttendanceMode($json);
                $json = $this->polyfillBookingAvailability($json);
                $json = $this->polyfillSubEventProperties($json);
                $json = $this->polyfillEmbeddedPlaceStatus($json);
                $json = $this->polyfillEmbeddedPlaceBookingAvailability($json);
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
                'type' => StatusType::available()->toNative(),
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
                            'type' => StatusType::available()->toNative(),
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
                'type' => StatusType::available()->toNative(),
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

        $json['sameAs'] = (new SameAsForUitInVlaanderen())->generateSameAs($id, $json['name']['nl']);

        return $json;
    }

    private function removeObsoleteProperties(JsonDocument $jsonDocument): JsonDocument
    {
        $obsoleteProperties = ['calendarSummary'];

        return $jsonDocument->applyAssoc(
            function (array $json) use ($obsoleteProperties) {
                $json = array_diff_key($json, array_flip($obsoleteProperties));
                return $json;
            }
        );
    }
}
