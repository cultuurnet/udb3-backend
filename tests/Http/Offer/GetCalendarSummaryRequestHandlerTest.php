<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\HtmlResponse;
use CultuurNet\UDB3\Http\Response\PlainTextResponse;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\OfferJsonDocumentReadRepositoryMockFactory;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;

class GetCalendarSummaryRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private OfferJsonDocumentReadRepositoryMockFactory $repositoryMockFactory;
    private GetCalendarSummaryRequestHandler $getCalendarSummaryRequestHandler;

    protected function setUp(): void
    {
        $this->repositoryMockFactory = new OfferJsonDocumentReadRepositoryMockFactory();

        $this->getCalendarSummaryRequestHandler = new GetCalendarSummaryRequestHandler(
            $this->repositoryMockFactory->create()
        );
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_if_the_given_offer_does_not_exist(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', '5ba3596f-5682-4cf5-85a9-306f9d0b0c34')
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::eventNotFound('5ba3596f-5682-4cf5-85a9-306f9d0b0c34'),
            fn () => $this->getCalendarSummaryRequestHandler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_returns_a_html_calendar_summary_based_on_the_accept_header(): void
    {
        $eventId = '1a16eff4-7745-4bd6-85b8-5bbbfffe3c96';
        $eventJson = Json::encode(
            [
                '@context' => '/contexts/event',
                'calendarType' => 'single',
                'startDate' => '2021-01-01T00:00:00+01:00',
                'endDate' => '2021-01-10T00:00:00+01:00',
                'status' => ['type' => 'Available'],
                'bookingAvailability' => ['type' => 'Available'],
            ]
        );
        $eventDocument = new JsonDocument($eventId, $eventJson);
        $this->repositoryMockFactory->expectEventDocument($eventDocument);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', $eventId)
            ->withHeader('accept', 'text/html')
            ->build('GET');

        $expectedContent = '<time itemprop="startDate" datetime="2021-01-01T00:00:00+01:00"><span class="cf-from cf-meta">Van</span> <span class="cf-weekday cf-meta">vrijdag</span> <span class="cf-date">1 januari 2021</span> <span class="cf-at cf-meta">om</span> <span class="cf-time">00:00</span></time> <span class="cf-to cf-meta">tot</span> <time itemprop="endDate" datetime="2021-01-10T00:00:00+01:00"><span class="cf-weekday cf-meta">zondag</span> <span class="cf-date">10 januari 2021</span> <span class="cf-at cf-meta">om</span> <span class="cf-time">00:00</span></time>';

        $response = $this->getCalendarSummaryRequestHandler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals($expectedContent, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function it_returns_a_html_calendar_summary_based_on_the_style_query_parameter(): void
    {
        $eventId = '1a16eff4-7745-4bd6-85b8-5bbbfffe3c96';
        $eventJson = Json::encode(
            [
                '@context' => '/contexts/event',
                'calendarType' => 'single',
                'startDate' => '2021-01-01T00:00:00+01:00',
                'endDate' => '2021-01-10T00:00:00+01:00',
                'status' => ['type' => 'Available'],
                'bookingAvailability' => ['type' => 'Available'],
            ]
        );
        $eventDocument = new JsonDocument($eventId, $eventJson);
        $this->repositoryMockFactory->expectEventDocument($eventDocument);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', $eventId)
            ->withUriFromString('/events/1a16eff4-7745-4bd6-85b8-5bbbfffe3c96/calendar-summary?style=html')
            ->build('GET');

        $expectedContent = '<time itemprop="startDate" datetime="2021-01-01T00:00:00+01:00"><span class="cf-from cf-meta">Van</span> <span class="cf-weekday cf-meta">vrijdag</span> <span class="cf-date">1 januari 2021</span> <span class="cf-at cf-meta">om</span> <span class="cf-time">00:00</span></time> <span class="cf-to cf-meta">tot</span> <time itemprop="endDate" datetime="2021-01-10T00:00:00+01:00"><span class="cf-weekday cf-meta">zondag</span> <span class="cf-date">10 januari 2021</span> <span class="cf-at cf-meta">om</span> <span class="cf-time">00:00</span></time>';

        $response = $this->getCalendarSummaryRequestHandler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals($expectedContent, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function it_returns_a_text_calendar_summary_based_on_the_accept_header(): void
    {
        $eventId = '1a16eff4-7745-4bd6-85b8-5bbbfffe3c96';
        $eventJson = Json::encode(
            [
                '@context' => '/contexts/event',
                'calendarType' => 'single',
                'startDate' => '2021-01-01T00:00:00+01:00',
                'endDate' => '2021-01-10T00:00:00+01:00',
                'status' => ['type' => 'Available'],
                'bookingAvailability' => ['type' => 'Available'],
            ]
        );
        $eventDocument = new JsonDocument($eventId, $eventJson);
        $this->repositoryMockFactory->expectEventDocument($eventDocument);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', $eventId)
            ->withHeader('accept', 'text/plain')
            ->build('GET');

        $expectedContent = 'Van vrijdag 1 januari 2021 om 00:00 tot zondag 10 januari 2021 om 00:00';

        $response = $this->getCalendarSummaryRequestHandler->handle($request);

        $this->assertInstanceOf(PlainTextResponse::class, $response);
        $this->assertEquals($expectedContent, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function it_returns_a_text_calendar_summary_based_on_the_style_query_parameter(): void
    {
        $eventId = '1a16eff4-7745-4bd6-85b8-5bbbfffe3c96';
        $eventJson = Json::encode(
            [
                '@context' => '/contexts/event',
                'calendarType' => 'single',
                'startDate' => '2021-01-01T00:00:00+01:00',
                'endDate' => '2021-01-10T00:00:00+01:00',
                'status' => ['type' => 'Available'],
                'bookingAvailability' => ['type' => 'Available'],
            ]
        );
        $eventDocument = new JsonDocument($eventId, $eventJson);
        $this->repositoryMockFactory->expectEventDocument($eventDocument);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', $eventId)
            ->withUriFromString('/events/1a16eff4-7745-4bd6-85b8-5bbbfffe3c96/calendar-summary?style=text')
            ->build('GET');

        $expectedContent = 'Van vrijdag 1 januari 2021 om 00:00 tot zondag 10 januari 2021 om 00:00';

        $response = $this->getCalendarSummaryRequestHandler->handle($request);

        $this->assertInstanceOf(PlainTextResponse::class, $response);
        $this->assertEquals($expectedContent, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function it_uses_a_different_language_based_on_the_language_query_parameter(): void
    {
        $eventId = '1a16eff4-7745-4bd6-85b8-5bbbfffe3c96';
        $eventJson = Json::encode(
            [
                '@context' => '/contexts/event',
                'calendarType' => 'single',
                'startDate' => '2021-01-01T00:00:00+01:00',
                'endDate' => '2021-01-10T00:00:00+01:00',
                'status' => ['type' => 'Available'],
                'bookingAvailability' => ['type' => 'Available'],
            ]
        );
        $eventDocument = new JsonDocument($eventId, $eventJson);
        $this->repositoryMockFactory->expectEventDocument($eventDocument);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', $eventId)
            ->withUriFromString('/events/1a16eff4-7745-4bd6-85b8-5bbbfffe3c96/calendar-summary?language=fr')
            ->build('GET');

        $expectedContent = 'Du vendredi 1 janvier 2021 à 00:00 au dimanche 10 janvier 2021 à 00:00';

        $response = $this->getCalendarSummaryRequestHandler->handle($request);

        $this->assertInstanceOf(PlainTextResponse::class, $response);
        $this->assertEquals($expectedContent, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function it_uses_a_different_format_based_on_the_format_query_parameter(): void
    {
        $eventId = '1a16eff4-7745-4bd6-85b8-5bbbfffe3c96';
        $eventJson = Json::encode(
            [
                '@context' => '/contexts/event',
                'calendarType' => 'single',
                'startDate' => '2021-01-01T00:00:00+01:00',
                'endDate' => '2021-01-10T00:00:00+01:00',
                'status' => ['type' => 'Available'],
                'bookingAvailability' => ['type' => 'Available'],
            ]
        );
        $eventDocument = new JsonDocument($eventId, $eventJson);
        $this->repositoryMockFactory->expectEventDocument($eventDocument);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', $eventId)
            ->withUriFromString('/events/1a16eff4-7745-4bd6-85b8-5bbbfffe3c96/calendar-summary?format=xs')
            ->build('GET');

        $expectedContent = 'Van 1 jan tot 10 jan';

        $response = $this->getCalendarSummaryRequestHandler->handle($request);

        $this->assertInstanceOf(PlainTextResponse::class, $response);
        $this->assertEquals($expectedContent, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function it_uses_a_different_timezone_based_on_the_timezone_query_parameter(): void
    {
        $eventId = '1a16eff4-7745-4bd6-85b8-5bbbfffe3c96';
        $eventJson = Json::encode(
            [
                '@context' => '/contexts/event',
                'calendarType' => 'single',
                'startDate' => '2021-01-01T10:00:00+01:00',
                'endDate' => '2021-01-10T10:00:00+01:00',
                'status' => ['type' => 'Available'],
                'bookingAvailability' => ['type' => 'Available'],
            ]
        );
        $eventDocument = new JsonDocument($eventId, $eventJson);
        $this->repositoryMockFactory->expectEventDocument($eventDocument);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', $eventId)
            ->withUriFromString('/events/1a16eff4-7745-4bd6-85b8-5bbbfffe3c96/calendar-summary?timezone=Europe/Moscow')
            ->build('GET');

        // 2 hours later than the time info in the event JSON
        $expectedContent = 'Van vrijdag 1 januari 2021 om 12:00 tot zondag 10 januari 2021 om 12:00';

        $response = $this->getCalendarSummaryRequestHandler->handle($request);

        // Reset timezone to initial value so other tests don't start failing.
        date_default_timezone_set('Europe/Brussels');

        $this->assertInstanceOf(PlainTextResponse::class, $response);
        $this->assertEquals($expectedContent, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function it_hides_past_dates_if_hidePast_query_parameter_is_true(): void
    {
        $eventId = '1a16eff4-7745-4bd6-85b8-5bbbfffe3c96';
        $eventJson = Json::encode(
            [
                '@context' => '/contexts/event',
                'calendarType' => 'multiple',
                'startDate' => '2021-01-01T10:00:00+01:00',
                'endDate' => '2121-01-10T10:00:00+01:00',
                'subEvent' => [
                    0 => [
                        'id' => 0,
                        'startDate' => '2021-01-01T10:00:00+01:00',
                        'endDate' => '2021-01-10T10:00:00+01:00',
                        'status' => ['type' => 'Available'],
                        'bookingAvailability' => ['type' => 'Available'],
                    ],
                    1 => [
                        'id' => 1,
                        'startDate' => '2121-01-01T10:00:00+01:00',
                        'endDate' => '2121-01-10T10:00:00+01:00',
                        'status' => ['type' => 'Available'],
                        'bookingAvailability' => ['type' => 'Available'],
                    ],
                ],
                'status' => ['type' => 'Available'],
                'bookingAvailability' => ['type' => 'Available'],
            ]
        );
        $eventDocument = new JsonDocument($eventId, $eventJson);
        $this->repositoryMockFactory->expectEventDocument($eventDocument);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', $eventId)
            ->withUriFromString('/events/1a16eff4-7745-4bd6-85b8-5bbbfffe3c96/calendar-summary?hidePast=true')
            ->build('GET');

        // Only includes the dates from 2121 (100 years in the future), because the others are in the past.
        $expectedContent = 'Van woensdag 1 januari 2121 om 10:00 tot vrijdag 10 januari 2121 om 10:00';

        $response = $this->getCalendarSummaryRequestHandler->handle($request);

        $this->assertInstanceOf(PlainTextResponse::class, $response);
        $this->assertEquals($expectedContent, $response->getBody()->getContents());
    }
}
