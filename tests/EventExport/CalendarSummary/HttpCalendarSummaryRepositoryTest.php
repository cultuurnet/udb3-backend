<?php

declare(strict_types=1);

namespace CalendarSummary;

use CultuurNet\UDB3\EventExport\CalendarSummary\ContentType;
use CultuurNet\UDB3\EventExport\CalendarSummary\Format;
use CultuurNet\UDB3\EventExport\CalendarSummary\HttpCalendarSummaryRepository;
use CultuurNet\UDB3\EventExport\CalendarSummary\SummaryUnavailableException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Http\Client\Exception\HttpException;
use Http\Client\HttpClient;
use League\Uri\Schemes\Http;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HttpCalendarSummaryRepositoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_fetch_calendar_summaries_by_id_at_a_configured_location()
    {
        $offerId = 'D352C4E6-90C6-4120-8DBB-A09B486170CD';
        $expectedRequest = new Request(
            'GET',
            'http://uitdatabank.io/events/D352C4E6-90C6-4120-8DBB-A09B486170CD/calsum?format=lg',
            [
                'Accept' => 'text/plain',
            ]
        );

        $summariesLocation = Http::createFromString('http://uitdatabank.io/');
        /** @var HttpClient|MockObject $httpClient */
        $httpClient = $this->createMock(HttpClient::class);

        $repository = new HttpCalendarSummaryRepository($httpClient, $summariesLocation);

        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($expectedRequest)
            ->willReturn(new Response());

        $repository->get($offerId, ContentType::PLAIN(), Format::LARGE());
    }

    /**
     * @test
     */
    public function it_should_throw_an_unavailable_exception_when_processing_the_summary_request_fails()
    {
        $offerId = 'D352C4E6-90C6-4120-8DBB-A09B486170CD';
        /** @var HttpException|MockObject $httpException */
        $httpException = $this->createMock(HttpException::class);

        $summariesLocation = Http::createFromString('http://uitdatabank.io/');
        /** @var HttpClient|MockObject $httpClient */
        $httpClient = $this->createMock(HttpClient::class);

        $repository = new HttpCalendarSummaryRepository($httpClient, $summariesLocation);

        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->withAnyParameters()
            ->willThrowException($httpException);

        $this->expectException(SummaryUnavailableException::class);

        $repository->get($offerId, ContentType::PLAIN(), Format::LARGE());
    }
}
