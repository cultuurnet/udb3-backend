<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\DeparturePlaces;

use CultuurNet\UDB3\EventExport\Translation\TranslatedProperty;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Exception;
use stdClass;

final class DeparturePlaceResolver
{
    /**
     * @var array<string, DeparturePlace>
     */
    private array $resolved = [];

    public function __construct(private readonly DocumentRepository $placeRepository)
    {
    }

    public function reset(): void
    {
        $this->resolved = [];
    }

    /**
     * A place that no longer exists is skipped, so a deleted place cannot fail a whole export.
     *
     * @param string[] $placeUrls
     * @return DeparturePlace[]
     */
    public function resolve(array $placeUrls): array
    {
        $departurePlaces = [];

        foreach ($placeUrls as $placeUrl) {
            try {
                $departurePlaces[] = $this->fetchDeparturePlace($placeUrl);
            } catch (Exception) {
                continue;
            }
        }

        return $departurePlaces;
    }

    private function fetchDeparturePlace(string $placeUrl): DeparturePlace
    {
        $placeId = $this->parsePlaceIdFromUrl($placeUrl);

        if (!isset($this->resolved[$placeId])) {
            $fetchedPlace = $this->fetchPlace($placeId);
            if ($fetchedPlace === null) {
                throw new Exception('Could not resolve place ' . $placeId);
            }
            $this->resolved[$placeId] = $fetchedPlace;
        }

        return $this->resolved[$placeId];
    }

    private function fetchPlace(string $placeId): ?DeparturePlace
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

        $placeId = (string) array_pop($urlParts);

        if ($placeId === '') {
            throw new Exception('Could not parse placeId from url');
        }

        return $placeId;
    }
}
