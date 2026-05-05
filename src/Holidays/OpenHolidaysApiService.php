<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Holidays;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Json;
use DateTimeImmutable;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;

final class OpenHolidaysApiService implements HolidaysService
{
    private const BASE_URL = 'https://openholidaysapi.org';
    private const COUNTRY_ISO_CODE = 'BE';

    public function __construct(
        private readonly ClientInterface $client,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getHolidays(DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        $validFrom = $startDate->format('Y-m-d');
        $validTo = $endDate->format('Y-m-d');

        $publicHolidays = array_map(
            fn (array $holiday): array => [
                'startDate' => $holiday['startDate'],
                'endDate' => $holiday['endDate'],
                'type' => HolidayType::PublicHolidays->outputType(),
                'name' => $holiday['name'],
            ],
            $this->fetchRaw(HolidayType::PublicHolidays, $validFrom, $validTo)
        );

        $schoolHolidays = [];
        foreach ($this->fetchRaw(HolidayType::SchoolHolidays, $validFrom, $validTo) as $holiday) {
            foreach ($holiday['groups'] as $group) {
                $schoolHolidays[] = [
                    'startDate' => $holiday['startDate'],
                    'endDate' => $holiday['endDate'],
                    'type' => HolidayType::SchoolHolidays->outputType(),
                    'name' => $holiday['name'],
                    'region' => $group['shortName'],
                ];
            }
        }

        $combined = array_merge($publicHolidays, $schoolHolidays);

        usort($combined, fn (array $a, array $b) => $a['startDate'] <=> $b['startDate']);

        return $combined;
    }

    private function fetchRaw(HolidayType $type, string $validFrom, string $validTo): array
    {
        $query = http_build_query([
            'countryIsoCode' => self::COUNTRY_ISO_CODE,
            'validFrom' => $validFrom,
            'validTo' => $validTo,
        ]);

        $request = new Request('GET', self::BASE_URL . '/' . $type->value . '?' . $query);

        try {
            $response = $this->client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Unable to reach the OpenHolidays API.', ['exception' => $e]);
            throw ApiProblem::badGateway('Unable to reach the OpenHolidays API.');
        }

        if ($response->getStatusCode() !== 200) {
            $this->logger->error('OpenHolidays API returned a non-200 status.', [
                'endpoint' => $type->value,
                'status_code' => $response->getStatusCode(),
            ]);
            throw ApiProblem::badGateway('OpenHolidays API returned a non-200 status.');
        }

        try {
            $holidays = Json::decodeAssociatively($response->getBody()->getContents());
            if (!is_array($holidays)) {
                throw new \UnexpectedValueException();
            }
        } catch (\Throwable $e) {
            $this->logger->error('OpenHolidays API returned unexpected response body: ' . $e->getMessage());
            throw ApiProblem::badGateway('OpenHolidays API returned an unexpected response.');
        }

        return $holidays;
    }
}
