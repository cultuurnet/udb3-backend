<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

final class Sapi3CalendarSummaryServiceTest extends TestCase
{
    private const OFFER_ID = '1a16eff4-7745-4bd6-85b8-5bbbfffe3c96';

    /** @var ClientInterface&MockObject */
    private ClientInterface $httpClient;

    private Sapi3CalendarSummaryService $service;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);

        $this->service = new Sapi3CalendarSummaryService(
            new Uri('https://search.uitdatabank.be'),
            $this->httpClient,
            'my-api-key'
        );
    }

    /**
     * @test
     */
    public function it_requests_the_calendar_summary_of_an_event(): void
    {
        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $this->assertSame('GET', $request->getMethod());
                $this->assertSame(
                    'https://search.uitdatabank.be/calendar-summary/events/' . self::OFFER_ID
                    . '?language=fr&style=html&size=md&hidePast=true&timezone=Europe%2FParis',
                    (string) $request->getUri()
                );
                $this->assertSame('my-api-key', $request->getHeaderLine('X-Api-Key'));
                return true;
            }))
            ->willReturn(new Response(200, ['Content-Type' => 'text/html'], '<span>summary</span>'));

        $summary = $this->service->get(
            OfferType::event(),
            self::OFFER_ID,
            [
                'language' => 'fr',
                'style' => 'html',
                'size' => 'md',
                'hidePast' => 'true',
                'timezone' => 'Europe/Paris',
            ]
        );

        $this->assertSame('<span>summary</span>', $summary);
    }

    /**
     * @test
     */
    public function it_requests_the_calendar_summary_of_a_place(): void
    {
        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $this->assertSame(
                    'https://search.uitdatabank.be/calendar-summary/places/' . self::OFFER_ID,
                    (string) $request->getUri()
                );
                return true;
            }))
            ->willReturn(new Response(200, [], 'Alle dagen open'));

        $this->assertSame('Alle dagen open', $this->service->get(OfferType::place(), self::OFFER_ID, []));
    }

    /**
     * @test
     */
    public function it_omits_the_api_key_header_without_an_api_key(): void
    {
        $service = new Sapi3CalendarSummaryService(new Uri('https://search.uitdatabank.be'), $this->httpClient, null);

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(fn (RequestInterface $request): bool => !$request->hasHeader('X-Api-Key')))
            ->willReturn(new Response(200, [], 'Alle dagen open'));

        $service->get(OfferType::place(), self::OFFER_ID, []);
    }

    /**
     * @test
     */
    public function it_throws_document_does_not_exist_when_search_api_returns_404(): void
    {
        $this->httpClient->method('sendRequest')->willReturn(new Response(404));

        $this->expectException(DocumentDoesNotExist::class);

        $this->service->get(OfferType::event(), self::OFFER_ID, []);
    }

    /**
     * @test
     */
    public function it_throws_when_search_api_returns_an_error(): void
    {
        $this->httpClient->method('sendRequest')->willReturn(new Response(500));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Search API returned HTTP status 500');

        $this->service->get(OfferType::event(), self::OFFER_ID, []);
    }

    /**
     * @test
     */
    public function it_throws_when_search_api_cannot_be_reached(): void
    {
        $this->httpClient->method('sendRequest')
            ->willThrowException(new ConnectException('Connection refused', new Request('GET', '/')));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not reach the Search API');

        $this->service->get(OfferType::event(), self::OFFER_ID, []);
    }
}
