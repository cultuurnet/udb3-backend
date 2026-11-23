<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\CalendarSummary;

use CultuurNet\UDB3\Search\Sapi3CalendarSummaryService;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

final class Sapi3CalendarSummaryRepositoryTest extends TestCase
{
    private const EVENT_ID = '24b87f88-d380-4746-ad40-044a007ec4c9';

    /** @var ClientInterface&MockObject */
    private ClientInterface $httpClient;

    private Sapi3CalendarSummaryRepository $repository;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);

        $this->repository = new Sapi3CalendarSummaryRepository(
            new Sapi3CalendarSummaryService(new Uri('https://search.uitdatabank.be'), $this->httpClient, 'api-key')
        );
    }

    /**
     * @test
     */
    public function it_gets_a_text_summary_of_an_event(): void
    {
        $this->expectSearchApiQuery(
            'language=nl&style=text&size=md&hidePast=false&timezone=Europe%2FBrussels',
            'Vr 23 sep - vr 7 okt'
        );

        $this->assertEquals(
            'Vr 23 sep - vr 7 okt',
            $this->repository->get(self::EVENT_ID, ContentType::plain(), Format::md())
        );
    }

    /**
     * @test
     */
    public function it_gets_a_html_summary_of_an_event(): void
    {
        $this->expectSearchApiQuery(
            'language=nl&style=html&size=lg&hidePast=false&timezone=Europe%2FBrussels',
            '<span>summary</span>'
        );

        $this->assertEquals(
            '<span>summary</span>',
            $this->repository->get(self::EVENT_ID, ContentType::html(), Format::lg())
        );
    }

    /**
     * @test
     * @dataProvider unavailableResponse
     */
    public function it_throws_summary_unavailable_when_the_search_api_has_no_summary(int $statusCode): void
    {
        $this->httpClient->method('sendRequest')->willReturn(new Response($statusCode));

        $this->expectException(SummaryUnavailableException::class);

        $this->repository->get(self::EVENT_ID, ContentType::plain(), Format::md());
    }

    public function unavailableResponse(): array
    {
        return [
            'not found' => [404],
            'server error' => [500],
        ];
    }

    /**
     * @test
     */
    public function it_throws_summary_unavailable_when_the_search_api_cannot_be_reached(): void
    {
        $this->httpClient->method('sendRequest')
            ->willThrowException(new ConnectException('Connection refused', new Request('GET', '/')));

        $this->expectException(SummaryUnavailableException::class);

        $this->repository->get(self::EVENT_ID, ContentType::plain(), Format::md());
    }

    private function expectSearchApiQuery(string $query, string $summary): void
    {
        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) use ($query): bool {
                $this->assertEquals('/calendar-summary/events/' . self::EVENT_ID, $request->getUri()->getPath());
                $this->assertEquals($query, $request->getUri()->getQuery());
                return true;
            }))
            ->willReturn(new Response(200, [], $summary));
    }
}
