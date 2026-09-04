<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\DeparturePlaces;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use stdClass;

/**
 * An event only stores the URLs of its departure places, so the exports look up each place to
 * describe it by name and municipality.
 */
final class DeparturePlaceResolver
{
    public function __construct(private readonly DocumentRepository $placeRepository)
    {
    }

    /**
     * Places that no longer exist are skipped, so a deleted place cannot fail a whole export.
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

        try {
            $place = Json::decode($this->placeRepository->fetch($placeId)->getRawBody());
        } catch (DocumentDoesNotExist) {
            return null;
        }

        return new DeparturePlace(
            $placeUrl,
            $this->getName($place),
            $this->getAddressField($place, 'postalCode'),
            $this->getAddressField($place, 'addressLocality')
        );
    }

    private function parsePlaceIdFromUrl(string $placeUrl): string
    {
        $urlParts = explode('/', rtrim($placeUrl, '/'));

        return (string) array_pop($urlParts);
    }

    private function getName(stdClass $place): string
    {
        if (!isset($place->name)) {
            return '';
        }

        if (is_string($place->name)) {
            return $place->name;
        }

        $translations = get_object_vars($place->name);
        $name = $translations[$this->getMainLanguage($place)] ?? reset($translations);

        return is_string($name) ? $name : '';
    }

    /**
     * @replay_i18n
     * @see https://jira.uitdatabank.be/browse/III-2201
     */
    private function getAddressField(stdClass $place, string $addressField): string
    {
        if (!isset($place->address)) {
            return '';
        }

        if (isset($place->address->{$addressField})) {
            return (string) $place->address->{$addressField};
        }

        $mainLanguage = $this->getMainLanguage($place);

        if (isset($place->address->{$mainLanguage}->{$addressField})) {
            return (string) $place->address->{$mainLanguage}->{$addressField};
        }

        $translations = get_object_vars($place->address);
        $address = reset($translations);

        if ($address instanceof stdClass && isset($address->{$addressField})) {
            return (string) $address->{$addressField};
        }

        return '';
    }

    private function getMainLanguage(stdClass $place): string
    {
        return is_string($place->mainLanguage ?? null) ? $place->mainLanguage : 'nl';
    }
}
