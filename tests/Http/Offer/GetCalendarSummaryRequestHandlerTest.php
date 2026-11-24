<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\HtmlResponse;
use CultuurNet\UDB3\Http\Response\PlainTextResponse;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\Search\CalendarSummaryService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetCalendarSummaryRequestHandlerTest extends TestCase
{
    private const OFFER_ID = '1a16eff4-7745-4bd6-85b8-5bbbfffe3c96';

    private CalendarSummaryService&MockObject $calendarSummaryService;

    private GetCalendarSummaryRequestHandler $getCalendarSummaryRequestHandler;

    protected function setUp(): void
    {
        $this->calendarSummaryService = $this->createMock(CalendarSummaryService::class);

        $this->getCalendarSummaryRequestHandler = new GetCalendarSummaryRequestHandler($this->calendarSummaryService);
    }

    /**
     * @test
     */
    public function it_returns_a_text_calendar_summary_by_default(): void
    {
        $this->calendarSummaryService->expects($this->once())
            ->method('get')
            ->with(
                OfferType::event(),
                self::OFFER_ID,
                [
                    'language' => 'nl',
                    'style' => 'text',
                    'size' => 'lg',
                    'hidePast' => 'false',
                    'timezone' => 'Europe/Brussels',
                ]
            )
            ->willReturn('Vrijdag 1 januari 2021');

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
        $this->calendarSummaryService->expects($this->once())
            ->method('get')
            ->with(
                OfferType::place(),
                self::OFFER_ID,
                $this->callback(fn (array $parameters): bool => $parameters['style'] === 'html')
            )
            ->willReturn('<span>Alle dagen open</span>');

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
    public function it_passes_the_deprecated_query_parameters_as_their_current_names(): void
    {
        $this->calendarSummaryService->expects($this->once())
            ->method('get')
            ->with(
                OfferType::event(),
                self::OFFER_ID,
                [
                    'language' => 'fr',
                    'style' => 'html',
                    'size' => 'sm',
                    'hidePast' => 'true',
                    'timezone' => 'Europe/Amsterdam',
                ]
            )
            ->willReturn('<span>Ven 1 jan</span>');

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
    public function it_lets_a_missing_offer_through_as_document_does_not_exist(): void
    {
        $this->calendarSummaryService->method('get')
            ->willThrowException(DocumentDoesNotExist::withId(self::OFFER_ID));

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->build('GET');

        $this->expectException(DocumentDoesNotExist::class);

        $this->getCalendarSummaryRequestHandler->handle($request);
    }
}
