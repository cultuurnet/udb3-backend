<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\DeparturePlaces;

use CultuurNet\UDB3\EventExport\Translation\TranslatedProperty;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use stdClass;

/**
 * An event stores its departure places as place URLs, which say nothing about where they are, so
 * describing one in an export needs a lookup.
 */
final class DeparturePlaceResolver
{
    /**
     * @var array<string, DeparturePlace|null>
     */
    private array $resolved = [];

    public function __construct(private readonly DocumentRepository $placeRepository)
    {
    }

    /**
     * A place that no longer exists is skipped, so a deleted place cannot fail a whole export.
     *
     * @return DeparturePlace[]
     */
    public function resolve(stdClass $event): array
    {
        if (!isset($event->departurePlaces) || !is_array($event->departurePlaces)) {
            return [];
        }

        $departurePlaces = [];

        foreach ($event->departurePlaces as $placeUrl) {
            if (!is_string($placeUrl)) {
                continue;
            }

            $departurePlace = $this->fetchDeparturePlace($placeUrl);

            if ($departurePlace !== null) {
                $departurePlaces[] = $departurePlace;
            }
        }

        return $departurePlaces;
    }

    private function fetchDeparturePlace(string $placeUrl): ?DeparturePlace
    {
        $placeId = $this->parsePlaceIdFromUrl($placeUrl);

        if ($placeId === '') {
            return null;
        }

        if (!array_key_exists($placeId, $this->resolved)) {
            $this->resolved[$placeId] = $this->describePlace($placeId);
        }

        return $this->resolved[$placeId];
    }

    private function describePlace(string $placeId): ?DeparturePlace
    {
        try {
            $place = Json::decode($this->placeRepository->fetch($placeId)->getRawBody());
        } catch (DocumentDoesNotExist) {
            return null;
        }

        if (!$place instanceof stdClass) {
            return null;
        }

        $mainLanguage = TranslatedProperty::mainLanguage($place);

        return new DeparturePlace(
            TranslatedProperty::asString($place->name ?? null, $mainLanguage),
            TranslatedProperty::addressField($place->address ?? null, 'postalCode', $mainLanguage),
            TranslatedProperty::addressField($place->address ?? null, 'addressLocality', $mainLanguage)
        );
    }

    private function parsePlaceIdFromUrl(string $placeUrl): string
    {
        $urlParts = explode('/', rtrim($placeUrl, '/'));

        return (string) array_pop($urlParts);
    }
}
