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
    private const SUBDIVISION_CODES = ['BE-VLG', 'BE-WAL', 'BE-BRU'];

    public function __construct(
        private readonly ClientInterface $client,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getHolidays(DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        $validFrom = $startDate->format('Y-m-d');
        $validTo = $endDate->format('Y-m-d');

        $publicHolidays = $this->fetchHolidays(HolidayType::PublicHolidays, $validFrom, $validTo);

        $schoolHolidays = [];
        foreach (self::SUBDIVISION_CODES as $subdivisionCode) {
            $schoolHolidays = array_merge(
                $schoolHolidays,
                $this->fetchHolidays(HolidayType::SchoolHolidays, $validFrom, $validTo, $subdivisionCode)
            );
        }

        $combined = array_merge($publicHolidays, $schoolHolidays);

        usort($combined, fn (array $a, array $b) => $a['startDate'] <=> $b['startDate']);

        return $combined;
    }

    private function fetchHolidays(
        HolidayType $type,
        string $validFrom,
        string $validTo,
        ?string $subdivisionCode = null
    ): array {
        $params = [
            'countryIsoCode' => self::COUNTRY_ISO_CODE,
            'validFrom' => $validFrom,
            'validTo' => $validTo,
        ];

        if ($subdivisionCode !== null) {
            $params['subdivisionCode'] = $subdivisionCode;
        }

        $query = http_build_query($params);

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
            $this->logger->error('OpenHolidays API returned unexpected response body: '. $e->getMessage(),);
            throw ApiProblem::badGateway('OpenHolidays API returned an unexpected response.');
        }

        return array_map(
            function (array $holiday) use ($type, $subdivisionCode): array {
                $item = [
                    'startDate' => $holiday['startDate'],
                    'endDate' => $holiday['endDate'],
                    'type' => $type->outputType(),
                    'name' => $holiday['name'],
                ];

                if ($subdivisionCode !== null) {
                    $item['subdivisionCode'] = $subdivisionCode;
                }

                return $item;
            },
            $holidays
        );
    }
}
