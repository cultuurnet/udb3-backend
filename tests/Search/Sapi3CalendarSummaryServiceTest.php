<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Json;
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

    /**
     * @test
     */
    public function it_gets_the_summaries_of_several_offers_with_one_search(): void
    {
        $otherId = '2b27aaf5-8856-4ce5-96c6-6c000aaaaaaa';

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) use ($otherId): bool {
                $this->assertSame('/events', $request->getUri()->getPath());
                parse_str($request->getUri()->getQuery(), $query);
                $this->assertSame(
                    [
                        'q' => 'id:(' . self::OFFER_ID . ' OR ' . $otherId . ')',
                        'limit' => '2',
                        'disableDefaultFilters' => 'true',
                        'embedCalendarSummaries' => ['md-text'],
                        'embedCalendarSummariesHidePast' => 'false',
                    ],
                    $query
                );
                $this->assertSame('my-api-key', $request->getHeaderLine('X-Api-Key'));
                return true;
            }))
            ->willReturn(new Response(200, [], Json::encode([
                'member' => [
                    [
                        '@id' => 'https://io.uitdatabank.be/events/' . self::OFFER_ID,
                        'calendarSummary' => [
                            'nl' => ['text' => ['md' => 'Di 5 mei 2020']],
                            'fr' => ['text' => ['md' => 'Mar. 5 mai 2020']],
                        ],
                    ],
                    [
                        '@id' => 'https://io.uitdatabank.be/events/' . $otherId,
                        'calendarSummary' => ['nl' => ['text' => ['md' => 'Alle dagen open']]],
                    ],
                ],
            ])));

        $summaries = $this->service->getMany(
            OfferType::event(),
            [self::OFFER_ID, $otherId],
            ['language' => 'nl', 'style' => 'text', 'size' => 'md', 'hidePast' => 'false']
        );

        $this->assertSame(
            [self::OFFER_ID => 'Di 5 mei 2020', $otherId => 'Alle dagen open'],
            $summaries
        );
    }

    /**
     * @test
     */
    public function it_leaves_out_offers_that_the_search_did_not_find(): void
    {
        $this->httpClient->method('sendRequest')->willReturn(new Response(200, [], Json::encode(['member' => []])));

        $this->assertSame(
            [],
            $this->service->getMany(OfferType::place(), [self::OFFER_ID], ['style' => 'html', 'size' => 'lg'])
        );
    }

    /**
     * @test
     */
    public function it_does_not_search_without_offers(): void
    {
        $this->httpClient->expects($this->never())->method('sendRequest');

        $this->assertSame([], $this->service->getMany(OfferType::event(), [], []));
    }

    /**
     * @test
     */
    public function it_throws_when_the_search_for_several_summaries_fails(): void
    {
        $this->httpClient->method('sendRequest')->willReturn(new Response(500));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Search API returned HTTP status 500 for the calendar summaries of 1 events');

        $this->service->getMany(OfferType::event(), [self::OFFER_ID], []);
    }
}
