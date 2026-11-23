<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\HtmlResponse;
use CultuurNet\UDB3\Http\Response\PlainTextResponse;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\Search\Sapi3CalendarSummaryService;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

class GetCalendarSummaryRequestHandlerTest extends TestCase
{
    private const OFFER_ID = '1a16eff4-7745-4bd6-85b8-5bbbfffe3c96';

    /** @var ClientInterface&MockObject */
    private ClientInterface $httpClient;

    private GetCalendarSummaryRequestHandler $getCalendarSummaryRequestHandler;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);

        $this->getCalendarSummaryRequestHandler = new GetCalendarSummaryRequestHandler(
            new Sapi3CalendarSummaryService(new Uri('https://search.uitdatabank.be'), $this->httpClient, 'api-key')
        );
    }

    /**
     * @test
     */
    public function it_returns_a_text_calendar_summary_from_the_search_api_by_default(): void
    {
        $this->expectSearchApiRequest(
            '/calendar-summary/events/' . self::OFFER_ID,
            'language=nl&style=text&size=lg&hidePast=false&timezone=Europe%2FBrussels',
            'Vrijdag 1 januari 2021'
        );

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->build('GET');

        $response = $this->getCalendarSummaryRequestHandler->handle($request);

        $this->assertInstanceOf(PlainTextResponse::class, $response);
        $this->assertEquals('Vrijdag 1 januari 2021', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function it_returns_a_html_calendar_summary_based_on_the_accept_header(): void
    {
        $this->expectSearchApiRequest(
            '/calendar-summary/places/' . self::OFFER_ID,
            'language=nl&style=html&size=lg&hidePast=false&timezone=Europe%2FBrussels',
            '<span>Alle dagen open</span>'
        );

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'places')
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withHeader('accept', 'text/html')
            ->build('GET');

        $response = $this->getCalendarSummaryRequestHandler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals('<span>Alle dagen open</span>', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function it_forwards_the_query_parameters_to_the_search_api(): void
    {
        $this->expectSearchApiRequest(
            '/calendar-summary/events/' . self::OFFER_ID,
            'language=fr&style=html&size=sm&hidePast=true&timezone=Europe%2FAmsterdam',
            '<span>Ven 1 jan</span>'
        );

        $request = (new Psr7RequestBuilder())
            ->withUriFromString('?langCode=fr_BE&style=html&format=sm&hidePast=true&timeZone=Europe/Amsterdam')
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->build('GET');

        $response = $this->getCalendarSummaryRequestHandler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals('<span>Ven 1 jan</span>', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function it_throws_when_the_search_api_does_not_find_the_offer(): void
    {
        $this->httpClient->method('sendRequest')->willReturn(new Response(404));

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->build('GET');

        $this->expectException(DocumentDoesNotExist::class);

        $this->getCalendarSummaryRequestHandler->handle($request);
    }

    private function expectSearchApiRequest(string $path, string $query, string $summary): void
    {
        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) use ($path, $query): bool {
                $this->assertEquals($path, $request->getUri()->getPath());
                $this->assertEquals($query, $request->getUri()->getQuery());
                return true;
            }))
            ->willReturn(new Response(200, [], $summary));
    }
}
