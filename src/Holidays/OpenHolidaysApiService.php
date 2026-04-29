<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Holidays;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Json;
use DateTimeImmutable;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;

final class OpenHolidaysApiService implements HolidaysService
{
    private const BASE_URL = 'https://openholidaysapi.org';
    private const COUNTRY_ISO_CODE = 'BE';

    public function __construct(private readonly ClientInterface $client)
    {
    }

    public function getHolidays(DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        $validFrom = $startDate->format('Y-m-d');
        $validTo = $endDate->format('Y-m-d');

        $publicHolidays = $this->fetchHolidays('PublicHolidays', 'holidays', $validFrom, $validTo);
        $schoolHolidays = $this->fetchHolidays('SchoolHolidays', 'schoolHolidays', $validFrom, $validTo);

        $combined = array_merge($publicHolidays, $schoolHolidays);

        usort($combined, fn (array $a, array $b) => $a['startDate'] <=> $b['startDate']);

        return $combined;
    }

    private function fetchHolidays(string $endpoint, string $type, string $validFrom, string $validTo): array
    {
        $query = http_build_query([
            'countryIsoCode' => self::COUNTRY_ISO_CODE,
            'validFrom' => $validFrom,
            'validTo' => $validTo,
        ]);

        $request = new Request('GET', self::BASE_URL . '/' . $endpoint . '?' . $query);

        try {
            $response = $this->client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw ApiProblem::badGateway('Unable to reach the OpenHolidays API: ' . $e->getMessage());
        }

        if ($response->getStatusCode() !== 200) {
            throw ApiProblem::badGateway(
                'OpenHolidays API returned status ' . $response->getStatusCode() . ' for ' . $endpoint
            );
        }

        $holidays = Json::decodeAssociatively($response->getBody()->getContents());

        return array_map(
            fn (array $holiday) => [
                'startDate' => $holiday['startDate'],
                'endDate' => $holiday['endDate'],
                'type' => $type,
                'name' => $holiday['name'],
            ],
            $holidays
        );
    }
}
